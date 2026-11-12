# Auditoria pública ampliada — produção

Data: 12/10/2026. Alvo: https://hackathon-ifpr-production.up.railway.app.
Esta rodada complementa [a primeira verificação](security-review-2026-10-12.md).
Fonte local consultada e reproduzida: commit `3a0706d82ff23273e1ad2f44fd2e6a9068976873`.
O commit local não comprova a versão implantada.

## Resultado e escopo

Foram concluídas 40 verificações HTTP adicionais em produção e 10 cenários distintos no código, com banco PostgreSQL temporário separado e notificações simuladas. Não houve criação de conta, alteração de senha, envio de e-mail ou modificação de dados do evento em produção. As requisições geraram sessões anônimas, registros de acesso/erro e contadores temporários de tentativas inválidas para endereços fictícios da auditoria.

Há quatro falhas reproduzidas, descritas abaixo. Uma foi reproduzida diretamente em produção; as outras foram reproduzidas com o código local para evitar usar contas reais, gerar tokens reais ou enviar e-mails. Nenhuma delas demonstrou tomada de conta ou execução remota de código.

Além disso, cadastro e recuperação não têm limite de requisições configurado nas rotas do aplicativo consultadas. Isso é uma lacuna de prevenção de abuso, descrita separadamente; não houve teste de sobrecarga ou envio massivo.

As correções de código e o deploy continuam com o Claude, conforme instrução do proprietário. Nesta rodada foram criados apenas este relatório e arquivos auxiliares de auditoria; nenhum arquivo de aplicação ou teste versionado foi alterado por esta auditoria.

## Falhas reproduzidas

### 1. Cadastro com e-mail em formato de lista retorna 500

**Confirmação: produção e reprodução isolada.** POST JSON em `/register`, com uma sessão anônima e CSRF válido. O retorno em produção foi HTTP 500 com a página de erro; não houve stack trace exposto.

Reprodução mínima usada no código isolado:

```json
{"name":"Audit User","email":["invalid"],"password":"bad","password_confirmation":"different"}
```

A confirmação diferente impede um cadastro válido independentemente do e-mail. A regra `string` falha, mas a validação continua até `lowercase`, que chama `mb_strtolower()` com uma lista e lança TypeError. A falha acontece antes de criar o usuário ou enviar a notificação.

Fonte: `app/Http/Controllers/Auth/RegisteredUserController.php:34-38`.
Impacto demonstrado: erro de servidor acionável sem login; não foi demonstrada indisponibilidade geral.
Correção sugerida: Form Request; `bail` antes das regras de tipo/formato; normalização apenas depois de confirmar que o valor é string. Regressão esperada: 422 com erro no e-mail, nenhum usuário e nenhuma notificação.

### 2. Token de recuperação em formato de lista causa 500

**Confirmação: reprodução isolada.** Criar usuário fictício e um token de recuperação não expirado no banco temporário; enviar POST em `/reset-password` com o e-mail desse usuário, uma nova senha válida e `"token":["invalid"]`.

O código valida o token somente como `required`. Quando existe um registro de token ainda válido para o usuário, a lista chega ao hasher e `password_verify()` lança TypeError. Sem usuário/registro de token válido, esse caminho pode retornar um erro comum em vez de 500. Essa condição é parte da reprodução.

Fonte: `app/Http/Controllers/Auth/NewPasswordController.php:37-47`.
Impacto demonstrado: erro de servidor condicionado à existência de recuperação pendente; não redefiniu a senha nem ultrapassou a checagem criptográfica.
Correção sugerida: validar tipo e tamanho do token no Form Request antes do broker; valor em formato de lista deve gerar 422 com erro no token.

Não foi reproduzido contra um e-mail real em produção.

### 3. Senha contendo byte nulo causa 500 no cadastro

**Confirmação: reprodução isolada.** POST em `/register` com usuário fictício, e-mail novo fictício e senha/confirmacão iguais a `AuditPassword!1\u0000`.

A senha atende à complexidade atual, mas o hash bcrypt rejeita o byte nulo. O fluxo retorna 500 antes de criar o usuário. A notificação simulada não foi enviada.

Fonte: `app/Http/Controllers/Auth/RegisteredUserController.php:37-43`.
Impacto demonstrado: entrada inválida provoca erro de servidor; não permite autenticação.
Correção sugerida: rejeitar byte nulo antes do hash e exigir senha textual, com limites compatíveis com o algoritmo de hash. Aplicar a regra central de forma consistente a cadastro, redefinição e troca de senha; verificar as outras duas rotas na regressão.

Não foi enviada uma tentativa de cadastro potencialmente válida a produção.

### 4. Redefinição de senha revela a existência do e-mail

**Confirmação: reprodução isolada dos dois casos; em produção foi confirmado somente o caso de e-mail fictício inexistente.**

Com a mesma senha válida e um token inválido, o código retorna respostas diferentes:

| E-mail | Mensagem |
|---|---|
| Conta fictícia existente | “Este link de redefinição de senha é inválido ou expirou.” |
| E-mail fictício inexistente | “Não encontramos nenhum usuário com esse e-mail.” |

Ambas têm HTTP 422. A diferença no conteúdo permite distinguir contas sem possuir um token válido. O endpoint `/forgot-password` já retorna a mesma mensagem genérica para existente/inexistente; essa proteção não se estende ao POST de redefinição.

Fonte: `app/Http/Controllers/Auth/NewPasswordController.php:65-66` e `lang/pt_BR/passwords.php`.
Impacto demonstrado: exposição da existência de conta; não houve enumeração de uma lista de pessoas em produção.
Correção sugerida: usar a mesma mensagem pública para usuário inexistente, token inválido ou expirado. Preservar o sucesso normal quando o token válido for usado.

A validação `unique` do cadastro também distingue e-mails já cadastrados. Se a meta for ocultar integralmente a existência de contas, esse fluxo precisa ser considerado na decisão de produto; mudar somente a mensagem de recuperação não elimina todas as formas de enumeração. A [OWASP recomenda respostas consistentes e proteção contra abuso na recuperação](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html).

## Lacuna de prevenção de abuso

No código consultado, os POSTs de `/register`, `/forgot-password` e `/reset-password` não incluem middleware de throttle. Não foi encontrado um limitador geral compensando isso nesses controllers. O broker limita a emissão repetida de token para a mesma conta por 60 segundos; isso não é um limite global por origem e não cobre criação de contas.

Fontes: `routes/auth.php:18,35,41` e `config/auth.php`.
Recomendação: definir limites por origem e, quando pertinente, por endereço normalizado, com cache compartilhado e respostas claras. Usar limites proporcionais ao evento. [Referência oficial do Laravel](https://laravel.com/framework/docs/12.x/rate-limiting).

Não foi feita uma prova de spam/saturação em produção nem verificada a existência de limites adicionais da plataforma.

## Controles que funcionaram

- O limite de login bloqueou a sexta tentativa inválida consecutiva. A resposta foi 422 com “Tentativas demais”, conforme o Form Request, não 429. A sétima tentativa, com `X-Forwarded-For` forjado, permaneceu bloqueada.
- Esse teste usou um endereço fictício e a mesma conexão HTTP reutilizada: o curl abriu uma conexão na primeira requisição e zero novas nas seis seguintes. Nenhuma conta real foi bloqueada.
- Entradas de injeção em slugs e UUIDs retornaram 404; campos de e-mail com conteúdo inválido retornaram 422. Não houve indicador de SQLSTATE/stack trace nas respostas observadas.
- Parâmetro de injeção em `/resultados` não publicou resultados: JSON continuou com `publicado=false` e pódio vazio. As queries públicas consultadas usam bindings de Eloquent, sem concatenação desses valores em SQL.
- Marcadores HTML/SVG e fechamento de script no parâmetro de e-mail de redefinição apareceram como dados escapados nas props do Inertia. O parser do HTML não encontrou os elementos injetados. A página React usa o valor em input, sem inserção de HTML bruto nesse fluxo.
- `redirect`/`next` no login não provocaram redirecionamento externo. `redirect_uri` e `state` forjados no início do OAuth não substituíram os valores gerados pelo servidor. O destino autorizado continuou sendo o callback de produção.
- `X-Forwarded-Host`, `X-Forwarded-Proto` e `Forwarded` não refletiram o host fictício nas respostas testadas. Não foi demonstrado envenenamento do host.
- HTML e JSON da página inicial retornaram `Cache-Control: no-cache, private` e `Vary: X-Inertia`, sem Age observado. Não foi demonstrado vazamento por cache compartilhado.
- O código isolado rejeitou token de outra conta, token expirado e reuso de token consumido. Cadastro ignorou campos de papel administrativo, verificação de e-mail, Google ID e QR token forjados.
- A solicitação de recuperação retornou a mesma resposta pública para conta existente e inexistente no teste isolado, com somente a notificação esperada para o usuário fictício existente.

## Pendências e limites

- Nas respostas de produção desta rodada, os cabeçalhos anti-framing/CSP/nosniff ainda não apareceram. O commit local `3a0706d` contém uma correção de cabeçalhos do Claude; sua presença em produção precisa de verificação depois do deploy.
- A demonstração visual de enquadramento e a execução de marcadores XSS em navegador não foram concluídas. O navegador conectado já possuía uma sessão autenticada; a guia de teste foi fechada sem interações. Uma página de prova sem credenciais via URL `data:` foi rejeitada pela política do navegador, que permite HTTP/HTTPS. O bloqueio não foi contornado.
- Portanto, esta rodada não afirma XSS executado nem clickjacking explorado. As evidências de XSS aqui são inspeção do HTML e do código; a evidência de enquadramento continua sendo a ausência de cabeçalhos.
- O GET da página de redefinição aceitou `email[key]=audit` e enviou uma prop de e-mail como objeto, apesar do tipo string no frontend. Isso foi registrado como problema de validação/tipagem; não demonstrou invasão.
- Os testes foram limitados a entradas públicas e cenários de autenticação isolados. A autorização entre duas contas reais de participante/jurado, uploads autenticados e todas as possíveis variantes de injeção/XSS continuam fora desta rodada.
- Não houve alteração de código, correção ou deploy por esta auditoria. Os arquivos temporários que continham cookies/CSRF foram removidos. O banco temporário é exclusivo da auditoria, separado do banco de desenvolvimento e do banco usado pelo Claude.

## Artefatos de reprodução

Os auxiliares ficam em `/tmp/hackathon-prod-deep-audit-5qMbyS8N/`:

- `PublicSecurityAuditTest.php`: 10 cenários independentes; guarda que exige o banco temporário `hackathon_security_audit_5qmby`.
- `isolated-junit.xml`: primeira execução de 9 cenários, 5 passaram e 4 falharam nas proteções esperadas.
- `array-email-junit.xml`: cenário adicional que reproduziu o 500 do e-mail em formato de lista.
- `http-results.json`: 40 registros HTTP sem valores de cookies, CSRF ou tokens OAuth.

As falhas dos testes são intencionais como critérios de proteção: quatro evidenciam os defeitos acima e uma identifica a ausência de throttle nas rotas consultadas. Após as correções, os critérios devem passar. Os testes auxiliares não foram inseridos na suíte versionada para evitar conflito com o trabalho do Claude.
