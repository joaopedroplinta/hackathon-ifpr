# Revisão de interface — evidências para o Codex

Acompanha a implementação de `docs/plano-repaginada-completa.md`. Uma linha
por página das 49 listadas na seção 5 do plano. Preenchido durante o
trabalho, não depois — status "pendente" é o padrão até verificação real.

**Ambiente de verificação:** `docker compose up -d`, `php artisan migrate`,
`npm run dev`, `php artisan serve` (subiu em `127.0.0.1:8017` nesta sessão —
porta 8000 ocupada por processo externo), `php artisan queue:work`. Banco
com dados do `DemoSeeder` (evento passado encerrado + evento atual com
inscrições abertas). Contas de teste: ver seção "Contas" no fim deste
arquivo.

**Evidência visual do Lote A:** `tests/Browser/public-ui.mjs` executou a
matriz com Chromium em 320, 390, 768 e 1440px, nos temas claro e escuro,
com dados fictícios isolados. As 80 verificações não encontraram overflow
horizontal nem erro de página. As 40 capturas de 390 e 1440px estão em
`/tmp/hackathon-lote-a-evidence/` nesta sessão; não são versionadas por serem
artefatos locais. O navegador não gravou dados de voto no banco: as respostas
de Inertia foram simuladas no teste.

Legenda de status de implementação: `feito` (revisado e ajustado nesta
tarefa), `ja-conforme` (auditado, já seguia o padrão, sem mudança de
conteúdo necessária), `pendente` (ainda não auditado).

Legenda de validação: `navegador` (interação real verificada nesta sessão,
desktop 1920px, claro e escuro), `código` (revisão de classes/lógica, sem
captura visual), `pendente`.

## Componentes compartilhados (seção 4 do plano)

Criados em `resources/js/components/hackathon/`:

- `pagina.tsx` — `ContainerPagina` (larguras `formulario|leitura|operacao|painel`) e `CabecalhoPagina` (eyebrow + título + descrição + ação).
- `estado-vazio.tsx` — `EstadoVazio` (ícone, título, descrição, ação opcional).
- `status.tsx` — `Status` (selo ícone+texto, 5 tons; nunca só cor).
- `secao-formulario.tsx` — `SecaoFormulario` (título + instrução + campos).
- `paginacao.tsx` — `Paginacao` (links do Laravel paginator, alvo de toque 44px).
- `resumo-erro.tsx` — `ResumoErro` (banner de erro de formulário para leitor de tela).
- `confirmar-acao.tsx` — `ConfirmarAcao` (Dialog de confirmação para ação sensível/destrutiva).

No Lote A, `EstadoVazio`, `Status` e `ResumoErro` já são usados. Também foram
criados `layouts/public-layout.tsx`, para manter cabeçalho e contexto
consistentes nas páginas públicas, e `documento-publico.tsx`, para navegação
por seções em regulamento, privacidade e cookies.

## Correção transversal já aplicada

- **Bug do skip link do cabeçalho público corrigido.** `cabecalho-publico.tsx`
  apontava `#conteudo-publico` para um `<span>` vazio logo após o header, não
  para o conteúdo real (item citado no plano, seção 5, Lote F). Removido o
  span; cada página pública agora carrega `id="conteudo-publico" tabIndex={-1}`
  no próprio `<main>`. Verificado via teclado nesta sessão: `Tab` → `Enter`
  no link "Pular para o conteúdo" move `document.activeElement` para o
  `<main>` real de `publico/inicio.tsx`. Validação: `navegador`.

## Lote A — Público (10/10 auditadas)

| Página                    | Papel/rota                                  | Estados vistos                             | Arquivos alterados                                                          | Implementação | Validação                            |
| ------------------------- | ------------------------------------------- | ------------------------------------------ | --------------------------------------------------------------------------- | ------------- | ------------------------------------ |
| `publico/inicio.tsx`      | `home`                                      | publicado, sem evento                      | repaginada anterior revisada; CTA contextual e texto de submissão corrigido | feito         | navegador, 4 tamanhos e 2 temas      |
| `publico/agenda.tsx`      | `agenda.index`                              | itens, filtro vazio, agenda vazia          | filtros por dia/tipo, horários, localização e download agrupados            | feito         | navegador, filtros e matriz visual   |
| `publico/projetos.tsx`    | `projetos.index`                            | busca, vazio, voto, erro, sucesso, fechado | busca, confirmação explícita, bloqueio concorrente e recibo de voto         | feito         | navegador; resposta de voto simulada |
| `publico/rubrica.tsx`     | `rubrica.show`                              | critérios, vazia                           | comparação de peso/nota e guia de leitura                                   | feito         | navegador                            |
| `publico/regulamento.tsx` | `regulamento.show`                          | arquivo/prazos                             | leitura por seções e navegação lateral; conteúdo preservado                 | feito         | navegador                            |
| `publico/resultados.tsx`  | `resultados.show`, `resultados.show.edicao` | publicado e não publicado                  | ranking legível, trilhas e prêmio popular                                   | feito         | navegador                            |
| `publico/edicoes.tsx`     | `edicoes.index`                             | lista e vazia                              | cartões de edição e próximo passo                                           | feito         | navegador                            |
| `publico/validar.tsx`     | `certificates.validate`                     | encontrado e não encontrado                | comprovante estruturado e orientação de validação                           | feito         | navegador                            |
| `publico/privacidade.tsx` | `privacidade.show`                          | conteúdo completo                          | leitura por seções; conteúdo jurídico preservado                            | feito         | navegador                            |
| `publico/cookies.tsx`     | `cookies.show`                              | conteúdo completo                          | leitura por seções; conteúdo preservado                                     | feito         | navegador                            |

**Limite conhecido do Lote A:** o download `.ics` foi preservado e o link foi
verificado na interface; a importação em aplicativo de calendário não foi
automatizada. O fluxo de voto foi testado contra uma resposta Inertia simulada
e os 48 testes públicos/da votação passaram contra PostgreSQL.

## Lote B — Autenticação e conta (9/9 auditadas)

| Página | Papel/rota | Estados revisados | Arquivos/resultado | Implementação | Validação/evidência |
| --- | --- | --- | --- | --- | --- |
| `auth/login.tsx` | `login` | credencial inválida, processamento, retorno | resumo de erro, hierarquia e campo de senha | feito | código; captura pendente |
| `auth/register.tsx` | `register` | requisitos, erros, processamento | erros próximos e campo de senha | feito | código; captura pendente |
| `auth/forgot-password.tsx` | `password.request` | envio, confirmação, nova tentativa | status e resumo de erro | feito | código; captura pendente |
| `auth/reset-password.tsx` | `password.reset` | token, requisitos, confirmação | status, erros e campo de senha | feito | código; captura pendente |
| `auth/confirm-password.tsx` | `password.confirm` | explicação, senha inválida, retorno | contexto, erro e campo de senha | feito | código; captura pendente |
| `auth/verify-email.tsx` | `verification.notice` | reenvio, feedback, saída | ação principal e status | feito | código; captura pendente |
| `settings/profile.tsx` | `profile.edit` | foto, dados, salvar, exclusão | seções, resumo de erro e ação destrutiva separada | feito | código; captura pendente |
| `settings/password.tsx` | `password.edit` | requisitos, erro, atualização | seção e mostrar/ocultar senha | feito | código; captura pendente |
| `settings/appearance.tsx` | `appearance.edit` | claro, escuro, sistema | seleção acessível e estado atual | feito | código; captura pendente |

Também foi auditado `layouts/settings/layout.tsx`: usa a URL do Inertia e
oferece navegação responsiva; não depende de `window` durante o render.

## Lote C — Participante (9/9 auditadas)

| Página | Papel/rota | Estados revisados | Arquivos/resultado | Implementação | Validação/evidência |
| --- | --- | --- | --- | --- | --- |
| `dashboard.tsx` | `dashboard` | papéis acumulados, perfil incompleto, próxima ação | aviso de dados do certificado e jornada existente auditada | feito | código; 9 testes/113 asserções; captura pendente |
| `inscricao/criar.tsx` | `inscricao.create` | janela, erro, campos opcionais | seções, erros e coleta condicional | feito | código; captura pendente |
| `equipe/sem-equipe.tsx` | `equipe.sem-equipe` | criar, entrar, bloqueio | orientação existente auditada | ja-conforme | código; captura pendente |
| `equipe/criar.tsx` | `equipe.create` | tamanho, trilha, erro, envio | seção e resumo de erro | feito | código; captura pendente |
| `equipe/entrar.tsx` | `equipe.join` | código inválido, equipe cheia, envio | seção e resumo de erro | feito | código; captura pendente |
| `equipe/minha.tsx` | `equipe.minha` | membros, convite, saída, remoção | fallback de cópia e confirmações | feito | código; captura pendente |
| `submissao/minha.tsx` | `submissao.minha` | rascunho, envio, prazo, bloqueio | resumo de erro e ações agrupadas | feito | código; captura pendente |
| `credencial/mostrar.tsx` | `credencial.show` | QR, identificação, instrução | leitura em celular auditada | feito | código; captura pendente; leitura real do QR pendente |
| `certificados/index.tsx` | `certificados.index` | disponível, vazio, download | cartões e estados de certificado | feito | código; captura pendente |

## Lote D — Jurado (2/2 auditadas)

| Página | Papel/rota | Estados revisados | Arquivos/resultado | Implementação | Validação/evidência |
| --- | --- | --- | --- | --- | --- |
| `jurado/fila.tsx` | `jurado.fila` | pendente, enviada, vazia, títulos longos | progresso e pendências visíveis | feito | código; captura pendente |
| `jurado/avaliar.tsx` | `jurado.avaliar` | zero, vazio, rascunho, erro, envio final | escala, peso, erros e estado explícitos | feito | código; captura pendente |

## Lote E — Organização e administração (18/18 auditadas)

| Página | Papel/rota | Estados revisados | Arquivos/resultado | Implementação | Validação/evidência |
| --- | --- | --- | --- | --- | --- |
| `admin/index.tsx` | `painel.index` | indicadores, prioridades, atalhos | painel existente auditado | ja-conforme | código; captura pendente |
| `admin/sem-evento.tsx` | `painel.sem-evento` | primeiro acesso, permissão | orientação existente auditada | ja-conforme | código; captura pendente |
| `admin/evento/criar.tsx` | `painel.evento.create` | dados, janelas, erros | seções de formulário e resumo | feito | código; captura pendente |
| `admin/evento/editar.tsx` | `painel.evento.edit` | calendário, limites, certificado, salvar | seções e coleta opcional de inscrição | feito | código; captura pendente |
| `admin/agenda/index.tsx` | `painel.agenda.index` | vazio, publicação, exclusão | lista operacional e confirmação | feito | código; captura pendente |
| `admin/agenda/formulario.tsx` | `painel.agenda.create/edit` | tipo, datas, relações, erro | campos condicionais e feedback | feito | código; captura pendente |
| `admin/checkin/index.tsx` | `painel.checkin.index` | scanner, origem inválida, busca manual | fallback manual e validação de origem do QR | feito | código; captura pendente |
| `admin/checkin/confirmar.tsx` | `painel.checkin.confirm` | checkpoint, repetição, envio | confirmação e resumo de erro | feito | código; captura pendente |
| `admin/incidentes/index.tsx` | `painel.incidentes.index` | histórico, extensão, erro | confirmação para extensão de prazo | feito | código; captura pendente |
| `admin/submissoes/index.tsx` | `painel.submissoes.index` | filtro, vazio, paginação, exportação | filtros e tabela operacional | feito | código; captura pendente |
| `admin/submissoes/mostrar.tsx` | `painel.submissoes.show` | arquivo, histórico, situação | detalhe e ações sensíveis auditados | feito | código; captura pendente |
| `admin/submissoes/lancar.tsx` | `painel.submissoes.lancar` | contingência, origem, erro | resumo de erro e fluxo rápido | feito | código; captura pendente |
| `admin/rubrica/index.tsx` | `painel.rubrica.index` | criar, ativar, excluir | lista, resumo de erro e confirmação | feito | código; captura pendente |
| `admin/rubrica/mostrar.tsx` | `painel.rubrica.show` | critérios, edição, exclusão | `ConfirmarAcao`, estrutura e resumo de erro | feito | código; captura pendente |
| `admin/jurados/index.tsx` | `painel.jurados.index` | carga, conflito, distribuição, reabertura | controles operacionais auditados | feito | código; captura pendente |
| `admin/resultados/index.tsx` | `painel.resultados.index` | recalcular, pendências, publicar | confirmação de publicação e feedback | feito | código; captura pendente |
| `admin/certificados/index.tsx` | `painel.certificados.index` | emissão, tipo, situação, download | formulário e lista auditados | feito | código; captura pendente |
| `admin/usuarios/index.tsx` | `painel.usuarios.index` | papéis, filtro, processamento | gestão de papéis auditada | feito | código; captura pendente |

## Lote F — Erros e superfícies transversais (1/1 auditada)

| Página/superfície | Papel/rota | Estados revisados | Arquivos/resultado | Implementação | Validação/evidência |
| --- | --- | --- | --- | --- | --- |
| `errors/erro.tsx` e componentes transversais | erros, modais, menus, avisos e navegação | 403/404/500, teclado, foco, tema, upload, QR | orientação por status; skip link, alvos de toque e fallbacks auditados | feito | código; captura autenticada pendente |

Permanecem pendentes a captura visual autenticada das áreas internas e a
regressão funcional ponta a ponta; a matriz pública segue automatizada.

## Alterações posteriores aos lotes

### Repaginação integral orientada pelo 21st.dev (2026-09-08)

A interface completa recebeu uma segunda passada visual na branch
`design/experimento-21st`. A alteração cobre as superfícies públicas, o shell
autenticado, autenticação, configurações, participante, jurado, organização,
administração e erros. Foram preservados os fluxos Inertia, props, regras de
domínio e primitives do shadcn existentes; o 21st.dev foi usado como fonte de
referência e os padrões foram adaptados à identidade do IFPR.

Referências consultadas via MCP do amazing 21st.dev:

- navegação e shell: `Dashboard Sidebar` #14941, `Sidebar` #2737 e
  `SidebarShowcase` #8252;
- jornada e progresso: `Onboarding Stages` #2528, `Onboarding Step Tracker`
  #24864, `ProjectProgressCard` #8698 e `Task Steps` #23569;
- autenticação e conta: `Sign In Split Screen` #19050, `Vertical Settings
  Tabs` #24937, `Account Settings Fieldset` #25058 e `Auth Change Password`
  #25261;
- operação: `Dashboard Overview` #8371, `Flexi Filter Table` #7466, `Form
  Layout` #4347, `Alert Dialog` #702 e `Stepper` #769;
- avaliação: `Rating Scale Group` #8241.

As referências não foram instaladas diretamente. Foram aproveitados hierarquia,
microcontraste, navegação por papel, progresso orientado à próxima ação,
agrupamento de formulários e clareza de estados. Cores, conteúdo, responsividade,
tema escuro, componentes e interações foram mantidos no design system local.

Validação visual: o script temporário de Playwright percorreu 49 renderizações
reais em `127.0.0.1:8000`, incluindo visitante, participante/admin e jurado,
em desktop claro e mobile escuro. Não houve overflow horizontal, erro de página,
resposta 5xx ou redirecionamento inesperado. As capturas e o relatório ficaram
em `/tmp/hackathon-system-evidence/` nesta sessão e não são versionados.

A matriz pública também foi repetida depois da integração: 80 combinações de
viewport/tema e 10 verificações de interação passaram. TypeScript, ESLint,
Prettier, build do Vite, Pint e `git diff --check` ficaram limpos. A suíte Pest
com PostgreSQL passou com **463 testes e 2204 asserções**. A execução inicial no
sandbox não alcançou `127.0.0.1:5432`; repetida com acesso ao banco de teste,
passou integralmente.

### Certificado PDF

Atualização em 2026-09-08: nova composição institucional A4 horizontal com
faixa lateral verde profunda, papel marfim, moldura e detalhes dourados,
ornamentos geométricos e tipografia serifada. Nome com tamanho adaptativo,
CPF/matrícula, participação, assinatura cadastrada e URL de validação
clicável e impressa. Mantidos logo/cor do snapshot e o cuidado da correção
do Claude para permanecer em uma página. Texto específico para jurado,
mentoria e organização.

Validação desta atualização: 11 testes de certificados/geração passaram
(17 asserções); Pint e diff limpos. PDFs renderizados pelo DomPDF e
inspecionados como imagem, com nome usual e nome de 255 caracteres,
evento/projeto longos: ambos em uma página, sem sobreposição na amostra.
Amostra fictícia local: `/tmp/hackathon-certificate-preview.pdf`.

`resources/views/certificates/pdf.blade.php` foi redesenhado diretamente para
o DomPDF: moldura editorial, faixa na cor configurada pelo evento, selo,
hierarquia de título/nome, bloco de participação e rodapé de assinatura e
validação. Logo e cor continuam sendo lidos do snapshot salvo no momento da
emissão; portanto PDFs já gerados não são alterados retroativamente. A
compilação das views (`php artisan view:cache`) e Pint passaram. Validação:
`código`.

### Coleta opcional na inscrição

A migration `2026_09_07_100000_add_registration_data_collection_to_events_table.php`
adiciona `collect_shirt_size` e `collect_dietary_notes`, ambos desligados por
padrão. A organização ativa cada finalidade em Evento; a inscrição mostra só
os campos habilitados e o Form Request os exclui de POSTs forjados quando a
coleta está desligada. Foram adicionados testes de configuração, exibição e
persistência. Requer `php artisan migrate`. Validação: `código`.

### Correções após fluxo ponta a ponta (2026-09-08)

Os fluxos reais revisados externamente encontraram três regressões, todas
corrigidas nesta sessão:

- `ResetPasswordQueued` substitui a notificação padrão do Laravel. O e-mail
  de redefinição de senha agora é enfileirado e inteiramente em português;
  `User::sendPasswordResetNotification()` garante seu uso pelo broker.
- O status de solicitação de redefinição usa
  `auth.password_reset_link_sent` em `lang/pt_BR/auth.php`, sem texto inglês
  residual e sem revelar se o e-mail existe.
- `jurado/avaliar.tsx` reconhece uma rubrica sem critérios: comunica que a
  organização deve configurá-la e mantém o envio desabilitado, em vez de
  informar incorretamente que a avaliação está pronta.

Os testes específicos de senha/notificação e avaliação passaram com **15
testes e 48 asserções**. Validação da interface de jurado: `código`; uma
nova captura autenticada continua pendente.

## Regressão funcional (seção 8.2) — não executada ainda

Nenhum dos 6 fluxos da seção 8.2 foi percorrido ponta a ponta nesta sessão.

## Comandos de verificação executados

```
npx tsc --noEmit
npm run lint:check
./vendor/bin/pint --test
npm run format:check
npm run build -- --logLevel error
git diff --check
/usr/bin/zsh -lc 'PLAYWRIGHT_MODULE=/tmp/hackathon-public-review/node_modules/playwright/index.mjs PLAYWRIGHT_BROWSERS_PATH=/tmp/hackathon-public-review/browsers node tests/Browser/public-ui.mjs'
```

TypeScript, lint, Pint, Prettier, build, diff e Playwright passaram na última
rodada registrada; o Playwright validou 80 combinações de viewport/tema e 10
interações públicas. Em 2026-09-07, `./vendor/bin/pest` também passou com
**458 testes e 2170 asserções**. A primeira tentativa falhou somente porque o
sandbox não alcançava o PostgreSQL local; repetido com acesso ao ambiente, o
container saudável respondeu normalmente.

## Contas de teste (banco local, senha `password`)

admin `joaopedrohenriqueplinta@gmail.com` · organizador
`organizacao@ifpr.edu.br` · jurado `janaina11@example.org` · participante-líder
`wchaves@example.org`.
