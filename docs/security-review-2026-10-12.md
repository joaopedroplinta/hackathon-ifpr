# Verificação externa de segurança — produção

Data: 12/10/2026. Alvo: https://hackathon-ifpr-production.up.railway.app.
Escopo autorizado: sem login, requisições sequenciais e de baixo volume.
Fonte local consultada: commit `9e1b269632202a74bd9e9eac4e6f3ed98231c779`; isso não comprova o commit implantado.

## Resultado

Foram executadas 35 verificações distintas, incluindo variações de cabeçalhos, protocolo e sessão. Não foi demonstrado acesso anônimo às áreas privadas nas tentativas realizadas. Há lacunas de proteção nos cabeçalhos HTTP. O teste não certifica a segurança do sistema nem cobre permissões entre usuários autenticados.

Os únicos POSTs foram envios vazios ao login, sem senha, usuário ou token CSRF. Nenhum cadastro, voto, envio de projeto, avaliação ou resultado foi alterado. Houve criação de sessões anônimas e registros normais de acesso/erro no servidor. Cookies temporários não foram incluídos nas evidências e foram descartados após as verificações.

## Achados

### 1. Ausência de proteção HTTP contra enquadramento

Evidência: respostas GET de `/` e `/login`, status 200, sem `X-Frame-Options` e sem `Content-Security-Policy` com `frame-ancestors`.

Isso deixa as páginas sem essas barreiras do navegador contra enquadramento por outro site. A exploração de clickjacking, incluindo interação autenticada ou captura de credenciais, não foi demonstrada. O cookie de sessão usa `SameSite=Lax`, o que também limita seu envio em iframes entre sites.

Prioridade: adicionar `Content-Security-Policy: frame-ancestors 'none'` e, para compatibilidade, `X-Frame-Options: DENY`, se o produto não precisar ser embutido. Confirmar em navegador depois da mudança. A [OWASP recomenda controle de enquadramento por CSP](https://cheatsheetseries.owasp.org/cheatsheets/Clickjacking_Defense_Cheat_Sheet.html).

### 2. Cabeçalhos adicionais de proteção ausentes

As respostas públicas verificadas também não apresentam CSP geral nem `X-Content-Type-Options`. A ausência de CSP não demonstra XSS, e a ausência de `nosniff` não demonstra execução de um upload.

Recomendação: adicionar `X-Content-Type-Options: nosniff`; definir CSP compatível com os scripts, estilos e recursos existentes, validando políticas de scripts em modo de relatório antes de exigir bloqueios. `Referrer-Policy` e `Permissions-Policy` explícitas podem documentar e restringir o comportamento desejado; sua ausência isolada não foi classificada como vulnerabilidade. [Referência da OWASP](https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html).

### 3. Observações sem vulnerabilidade demonstrada

- `/build/manifest.json` respondeu 200 a HEAD. Isso revela a disponibilidade do manifesto de assets, não comprova exposição de segredo ou código PHP. O conteúdo não foi baixado.
- A rota presumida do source map do bundle principal respondeu 404. Isso não verifica todos os bundles.
- `Strict-Transport-Security` não aparece nas respostas verificadas. HTTP redirecionou para HTTPS com 301. O domínio `.app` integra o HSTS preload, portanto a ausência do cabeçalho não foi tratada como falha de downgrade em navegadores que usam essa lista. [Fonte: Google Registry](https://www.registry.google/domains/app/).

## Controles observados

| Tentativa | Evidência |
|---|---|
| GET em `/dashboard`, `/admin/usuarios`, `/painel/submissoes`, `/painel/resultados`, `/jurado`, `/equipe` e `/submissao` | 302 para login |
| HEAD em download de arquivo, certificado e exportação de submissões | 302 para login; nenhum arquivo baixado |
| Cabeçalhos falsos `X-User-Role: admin` e `X-User-Id: 1` na área administrativa | Continuou redirecionando para login |
| JSON público de `/resultados` com versão correta do Inertia | `publicado=false`, pódios vazios e prêmio popular nulo |
| Query com `publicado=1` e `results_published_at` arbitrário | Continuou sem resultados publicados |
| Pedido parcial pelo Inertia de pódios e prêmio | Continuou retornando dados vazios |
| JSON público de `/projetos` | Nenhuma submissão pública na ocasião; voto indisponível para anônimo |
| POST vazio em `/login`, sem CSRF | 302; com a mesma sessão, a página seguinte exibiu “Sua sessão expirou. Tente novamente.” |
| Callback OAuth com state/code inválidos, sem fluxo de login prévio | 302 para login; sessão seguinte exibiu erro de state/sessão expirada |
| `X-Forwarded-Host` com domínio fictício no login | Domínio fictício não refletido no HTML ou redirecionamento observados |
| Origem externa fictícia em HEAD da página inicial | Sem cabeçalhos CORS permitindo a origem ou credenciais |
| Token malformado em validação de certificado e check-in; rota inexistente | 404 sem os indicadores de stack trace pesquisados |
| HEAD em `/.git/HEAD` e `/storage/app/private/` | 404; não houve leitura desses conteúdos |
| Cookie de sessão anônima | `Secure`, `HttpOnly`, `SameSite=Lax` |
| Cache das respostas dinâmicas observadas | `no-cache, private` |

O HTTP 302 no teste de CSRF é o tratamento customizado de sessão expirada, confirmado pela mensagem na requisição seguinte; não foi interpretado como aceitação do login. Os pedidos iniciais de JSON sem versão do Inertia retornaram 409; foram repetidos com a versão anunciada pela página para avaliar os dados efetivamente devolvidos.

## Limites e próxima etapa

Sem sessões de participante e jurado não foram testados acesso entre equipes, atribuição de jurados, alteração de papéis, uploads autenticados, prazos, votos ou integridade de notas. A galeria pública estava vazia: não foi possível examinar quais campos de uma submissão real apareceriam ali.

Não houve teste de carga, força bruta, exploração de infraestrutura ou auditoria completa de injeção/XSS. HEAD em caminhos específicos não substitui a revisão completa do armazenamento. Não foram realizadas correções ou deploys.

A próxima etapa mais útil é testar autorização com duas contas comuns controladas pelo proprietário e objetos de teste, mantendo dados reais fora das tentativas de alteração.
