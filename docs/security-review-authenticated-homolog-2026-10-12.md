# Auditoria autenticada — homologação

Data: 12/10/2026. Alvo exclusivo: https://hackathon-ifpr-homologacao.up.railway.app.
Edição: `hackathon-ifpr-demonstracao`, event_id 3. Estágio preservado: `resultado`.
A produção não foi acessada nesta rodada. As correções da aplicação continuam com o Claude.

## Resultado

Não foi demonstrado acesso indevido entre as equipes, escalada para administrador, alteração de avaliações entregues, publicação por participante/jurado ou voto duplicado.

Foram confirmados quatro casos de entrada malformada com retorno HTTP 500, em três rotas. Todos também foram reproduzidos no código local, em banco PostgreSQL temporário separado. Esses erros demonstram falta de robustez da validação; não demonstraram tomada de conta, leitura de dados alheios ou alteração de notas.

A execução registrou 263 requisições HTTP instrumentadas, além de 38 requisições preliminares de reconhecimento/login, em baixo volume e sem teste de carga. Foram registradas 170 asserções/observações: 164 passaram, quatro identificaram os erros 500 e duas tentativas preliminares de reabertura foram descartadas por payload inválido e repetidas corretamente.

## Contas e contexto

| Conta | Papel | Referência nesta rodada |
|---|---|---|
| organizacao.demo@demo.ifpr.test | Organizador, sem admin | Acesso legítimo ao painel e aos objetos de teste |
| jurado.demo@demo.ifpr.test | Jurado 1 | Submissão 66 atribuída e avaliação entregue |
| jurado2.demo@demo.ifpr.test | Jurado 2 | Submissão 67 visível na fila e avaliação entregue |
| participante.demo@demo.ifpr.test | Participante A | Rota Segura, equipe 90, submissão 65 |
| p03.demo@demo.ifpr.test | Participante B | Horta Viva, equipe 91, submissão 66 |

Os IDs são referências da rodada e podem mudar após regenerar a demonstração. A senha foi carregada do arquivo autorizado diretamente para memória, sem aparecer nos comandos, relatórios ou logs da auditoria. Cookies/CSRF também ficaram somente em memória. O arquivo original de credenciais não foi alterado.

Antes das tentativas de escrita, foram confirmados prazo encerrado, submissões existentes, avaliações em somente leitura, votação aberta e ranking calculado ainda não publicado.

## Controles que funcionaram

| Tentativa | Resultado observado |
|---|---|
| Participantes e jurados acessarem painel, lista de equipes/submissões, administração de jurados, usuários e ranking privado | 403 |
| A consultar o detalhe da submissão de B, e B consultar a de A | 403 |
| Trocar equipe/submissão por query parameters com IDs de outra pessoa | Continuou retornando o recurso do usuário autenticado |
| Jurado 1 acessar/enviar avaliação da submissão de Jurado 2 e vice-versa | 403 com payload válido |
| Regravar rascunho ou reenviar avaliação já entregue | 403; avaliações permaneceram iguais |
| Participante entrar na tela de avaliação de jurado | 403 |
| Salvar/reenviar projeto após o prazo, forjando equipe, status, versão e timestamps | 403; conteúdo, versões e arquivos permaneceram iguais |
| Remover integrante/sair usando membership de outra equipe | 403; composição das equipes preservada |
| Entrar na outra equipe pelo código depois do prazo | 403 |
| Conceder a si papel admin pela rota de papéis | 403 para participante e jurado; flags de papel preservadas |
| Injetar roles e user_id no formulário de perfil | Campos privilegiados ignorados; usuário autenticado e identidade preservados |
| Reabrir avaliação como participante ou jurado, com reason válido | 403 |
| Publicar/recalcular resultados como participante ou jurado | 403; resultado continuou oculto |
| Upload de PHP, HTML disfarçado de PDF, SVG disfarçado de PNG e avatar SVG | 422 |
| PDF com MIME permitido, após o prazo | 403 pela autorização de edição |
| Dono baixar anexo/certificado temporário existente | 200 |
| Outra equipe baixar/remover o anexo ou baixar o certificado | 403 |
| Jurado baixar anexo de submissão não atribuída ou certificado alheio | 403 |
| Organizador baixar os objetos temporários | 200 |
| Acesso anônimo a downloads privados existentes | Redirecionamento para login |
| Acesso direto ao caminho privado por /storage | 403, sem conteúdo |
| Voto na própria equipe ou em projeto excluído da vitrine | 422 |
| Organizador/jurado votar como participante | 403 |
| Forjar user_id/event_id no voto | O voto foi da conta autenticada e da edição atual |
| Votar novamente em outro projeto | Primeiro voto preservado e mensagem de voto já registrado |
| Duas requisições de voto enviadas simultaneamente | Retornos sem 500 e exatamente um registro no banco |
| Reusar cookie capturado antes do login, depois de autenticar | Continuou sem acesso, nas cinco contas |
| Reusar cookie autenticado capturado antes do logout | Redirecionou para login, nas cinco contas |

A alteração do texto cifrado do cookie, isoladamente, não prova rotação de ID. A proteção de sessão acima foi verificada pelo replay dos cookies anteriores e pelo controle positivo de acesso com a sessão autenticada.

### Prova do voto no banco

Após os votos de teste, a consulta de leitura na homologação retornou um registro para A e um para B. O banco também apresentou o índice:

```sql
CREATE UNIQUE INDEX popular_votes_event_id_user_id_unique
ON public.popular_votes USING btree (event_id, user_id)
```

Isso confirma a unicidade além da seleção exibida pelo frontend. As duas solicitações foram enviadas simultaneamente pelo cliente; não foi medida a ordem interna de execução dos workers.

## Erros 500 confirmados e reproduções

Todas as tentativas usaram sessão autenticada e CSRF válido, com Accept application/json. Nenhum CPF real foi utilizado.

| Caso | Rota | Entrada malformada | Causa reproduzida no código |
|---|---|---|---|
| E-mail como lista no perfil | PATCH /settings/profile | `email: ["invalid"]`, name válido | A regra lowercase continua após falhar string e passa uma lista para mb_strtolower |
| CPF como lista no perfil | PATCH /settings/profile | `cpf: ["audit-invalid"]`, name/e-mail válidos | prepareForValidation converte uma lista em texto antes de validar o tipo |
| Notas como texto no rascunho | POST /jurado/avaliar/66/rascunho | `scores: "audit-invalid"` | Callback after tenta foreach em texto apesar da regra array já ter falhado |
| Notas como texto no envio | POST /jurado/avaliar/66/enviar | `scores: "audit-invalid"` | Mesmo problema no callback de envio |

As tentativas nas rotas de avaliação foram feitas por Participante A, que não tinha atribuição. Com notas válidas esse acesso é negado por autorização; com formato inválido o Form Request gera erro antes de chegar à Policy no controller.

Fontes consultadas/reproduzidas no commit local `3ff246b307e9a397d38585a92938d4f07aca2645`:

- `app/Http/Requests/Settings/ProfileUpdateRequest.php:22` e `:36`.
- `app/Http/Requests/Judge/SaveEvaluationDraftRequest.php:52`.
- `app/Http/Requests/Judge/SubmitEvaluationRequest.php:64`.

A reprodução local registrou TypeError de mb_strtolower, ErrorException de conversão de array em string e ErrorException de foreach em string. Em homologação, as respostas mostraram a página de erro, sem os indicadores de stack trace pesquisados.

### Correções recomendadas

1. No perfil, interromper regras do campo após falha de tipo, antes de lowercase/unique.
2. Normalizar CPF somente quando o valor tiver tipo textual. Preservar valores de outros tipos para a validação rejeitá-los; também proteger a regra CpfValido contra conversões indevidas.
3. Nos dois callbacks de avaliação, retornar antes de iterar quando a validação básica já tiver erros; verificar a estrutura de scores/linhas.
4. Manter a autorização pela Policy. Para pessoas sem atribuição, considerar autorização antes da validação que consulta a rubrica.
5. Adicionar os quatro cenários à suíte de regressão e repetir as tentativas em homologação após o deploy.

A [regra bail do Laravel](https://laravel.com/framework/docs/12.x/validation#bail) interrompe regras subsequentes do campo. Os [callbacks de validação adicional](https://laravel.com/framework/docs/12.x/validation#performing-additional-validation) precisam tratar explicitamente os tipos recebidos; bail sozinho não substitui a guarda do callback.

## Observações sem invasão demonstrada

- O participante acessa o detalhe da própria submissão em /painel/submissoes/{id} com 200. Isso está permitido pela SubmissionPolicy compartilhada. O objeto continuou sendo o próprio; listas administrativas e ações privilegiadas foram negadas.
- Jurado 1, atribuído à submissão 66, recebeu 403 ao baixar seu anexo temporário. A SubmissionFilePolicy delega à SubmissionPolicy, que permite staff/membro da equipe. Trata-se de acesso funcional a esclarecer se os anexos devem integrar a avaliação; não houve exposição indevida.
- Cabeçalhos anti-framing/CSP/nosniff ainda não apareceram nas respostas observadas da homologação. Confirmar a aplicação das correções de cabeçalhos depois do deploy.
- O teste de upload aceito dentro do prazo e de primeira avaliação em aberto não foi executado: as equipes já haviam enviado e os jurados estavam em somente leitura. O comando de demonstração não foi reiniciado.
- A rodada não é uma prova completa de ausência de falhas de segurança e não inclui teste de carga.

## Limpeza e integridade final

Foram criados somente para os controles positivos dois anexos privados e dois certificados fictícios, identificados pelo marcador xdtgBZKn, nas contas A/B da edição de demonstração. Não houve emissão de certificado por notificação nem envio de e-mail real. Os downloads foram testados por HEAD.

Ao terminar, foram removidos exatamente os dois anexos, os dois certificados e os dois votos novos das contas que inicialmente não tinham voto. A limpeza usou marcador, edição, conta, seleção e período da rodada, sem reiniciar a demonstração.

A leitura final no servidor confirmou:

- 8 equipes e 7 submissões.
- Resultado não publicado.
- Zero certificados da edição e zero arquivos temporários, tanto no banco quanto no armazenamento.
- Zero votos nas duas contas da auditoria, restaurando o estado inicial.
- As comparações HTTP preservaram datas/status do evento, ranking, versões, composição de equipes e identidades.

Os controles adicionais de publicação/recalculo também retornaram 403 após essa conferência e deixaram o resultado público oculto. As sessões finais foram encerradas. As senhas das cinco contas foram preservadas.

## Artefatos para correção e reprodução

Diretório auxiliar local: `/tmp/hackathon-auth-audit-xdtgBZKn/`.

- `http-results.json` e `checks.json`: metadados das requisições e asserções, sem credenciais.
- `baseline.json`, `server-vote-verification.json` e `cleanup-verification.json`: estado inicial, unicidade e limpeza.
- `AuthenticatedValidationAuditTest.php`: quatro testes que reproduzem os erros 500; exige o banco isolado hackathon_security_audit_5qmby.
- `validation-reproductions.xml` e `cpf-shape-reproduction.xml`: resultados das reproduções locais.
- Helpers HTTP e scripts de criação/limpeza de fixtures: somente homologação, com guarda contra produção.

Os quatro testes de proteção falham antes da correção, conforme esperado nesta auditoria. Nenhum arquivo de aplicação ou teste versionado foi alterado por esta rodada; apenas o relatório foi acrescentado. Os auxiliares ficam fora da suíte para não disputar arquivos com o Claude.
