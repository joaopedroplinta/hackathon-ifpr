# Changelog

Mudanças notáveis deste projeto, por versão. Formato baseado em
[Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/); o projeto segue
[SemVer](https://semver.org/lang/pt-BR/) — enquanto a major for `0`, um
minor pode trazer mudança que quebra compatibilidade.

A versão exibida no rodapé da sidebar vem de `composer.json` (`version`);
bump manual a cada tag.

## [Não lançado]

## [1.3.4] - 2026-10-22

Correção de segurança apontada pelo Burp na homologação: o cabeçalho HSTS
não era enviado.

### Corrigido

- `Strict-Transport-Security` passa a ser enviado apenas em requisições HTTPS,
  sem `includeSubDomains` nem `preload`, porque o domínio `railway.app` não é
  nosso (#209)

## [1.3.3] - 2026-10-18

Correções de segurança da rodada de auditoria: contas, convites, edições
passadas, jurados, certificados, ranking e IP do cliente.

Atenção no deploy: a migration que adiciona `certificates.revoked_at` roda no
deploy. A revogação de certificado de colocação na desclassificação é decisão
de produto.

### Corrigido

- Troca de e-mail exige a senha atual (contas só-Google, sem senha, ficam
  isentas); trocar ou redefinir a senha derruba as outras sessões; vincular o
  Google a uma conta não verificada descarta senha e sessões; o e-mail da
  anonimização passou a ser um UUID, dentro de transação (#198)
- Convites de equipe: limite de 10 por minuto, teto de convites pendentes igual
  ao tamanho máximo da equipe, nome da equipe escapado no Markdown dos e-mails;
  aceitar convite exige inscrição e prazo aberto (#200)
- Rubrica, critério, agenda e reabertura de avaliação de edições passadas dão
  404; criar edição com outra em cartaz é recusado; resultado público de
  edição em rascunho dá 404 (#202)
- Jurado que é membro da equipe ou tem conflito registrado não é atribuído nem
  avalia; certificados de colocação são revogados na desclassificação (nova
  coluna `certificates.revoked_at`, migration inclusa) e a validação pública
  mostra "Certificado revogado"; dissolver a equipe remove a submissão; lotes
  de certificado e lembrete toleram usuário anonimizado (#204)
- Nota final não é mais distorcida por rascunho de rubrica antiga; voto popular
  de membro da equipe votada não conta; lançamento externo (formulário manual
  e importação CSV) recusa equipe ou submissão desclassificada (#206)
- O IP do cliente passa a vir do `X-Real-IP` da Railway; antes o throttle de
  login enxergava só o IP do proxy (#208)
- Requisições de avaliação com ids de critério malformados respondem 422 (#196)

## [1.3.2] - 2026-10-12

Política de segurança de conteúdo (CSP) e duas correções encontradas nos testes
em celular e na homologação.

### Adicionado

- Content-Security-Policy completa, com nonce para os scripts da página e só os
  domínios que o sistema já usa (fontes e foto do Google). O modo vem de
  `CSP_MODE` (`off`, `report-only` ou `enforce`); em produção está em
  `enforce` (#190)

### Corrigido

- Leitor de QR do check-in não abria a câmera no iPhone: agora abre pelo botão
  "Abrir câmera", o campo de busca não rouba mais o foco e, se falhar, a tela
  mostra o nome técnico do erro (#192)
- `hackathon:demo` gravava a agenda e a entrada três horas adiantadas (08:00
  UTC em vez de 08:00 de Brasília). Só afeta a homologação (#194)

## [1.3.1] - 2026-10-12

Correções de robustez e segurança de uma nova rodada de revisão (testes sem
login na produção e com contas na homologação) e uma ferramenta interna de
demonstração para a homologação.

### Adicionado

- Comando `hackathon:demo` para a homologação: prepara a edição de
  demonstração em qualquer etapa do fluxo (início, submissões, avaliação,
  resultado). Recusa rodar em produção (#180)
- Cabeçalhos de segurança em toda resposta: `frame-ancestors 'none'`,
  `X-Frame-Options: DENY`, `nosniff`, `Referrer-Policy` e `Permissions-Policy`
  com a câmera liberada só para o próprio site (leitor de QR). A CSP completa
  fica para depois, após teste em modo de relatório (#182)
- Limite de tentativas no cadastro (30 por minuto por IP), na recuperação e na
  redefinição de senha (10 por minuto) (#184)

### Alterado

- Redefinir senha com e-mail inexistente mostra a mesma mensagem do link
  inválido, para não revelar quais e-mails têm conta (#184)

### Corrigido

- E-mail ou token enviado como lista no cadastro e na redefinição de senha, e
  senha com byte nulo, davam erro 500; agora dão erro de validação (#184)
- E-mail ou CPF como lista no perfil e notas em formato inválido na avaliação
  do jurado davam erro 500; agora dão erro de validação (#186)
- Importação de CSV: endereços que não são http(s) viram conflito para
  conferência, e o comando não quebra mais com a coluna `email_lider` ausente
  ou com arquivo ilegível (#188)

## [1.3.0] - 2026-10-11

Decisão do organizador sobre envios fora do prazo, desclassificação de equipes
com tela própria e e-mails, e correções de uma segunda rodada da revisão de
segurança. Atenção: envio fora do prazo deixou de concorrer sozinho.

### Adicionado

- Decisão sobre envio fora do prazo: o organizador aceita ou recusa, sempre
  com motivo (10 a 500 caracteres) e autor no activity log, e a decisão pode
  ser revista. Novos status `late_accepted` e `late_rejected`; a tela da
  submissão ganha o cartão de decisão, o participante vê se o envio foi aceito
  ou recusado, e a tela de resultados lista os envios atrasados sem decisão
  como pendência antes de publicar (#174)
- Desclassificar e reabilitar equipe, com motivo e autor no activity log. A
  equipe e a submissão saem da avaliação, do ranking, da vitrine, da votação
  e da fila do jurado, e a equipe não envia mais; reabilitar devolve o estado
  anterior. Se o resultado já foi calculado, é recalculado na hora (#176)
- Tela Equipes no painel, com busca por nome e filtro de situação: permite
  desclassificar ou reabilitar inclusive equipe que ainda não enviou nada
  (#178)
- E-mail aos integrantes ativos da equipe ao ser desclassificada e ao ser
  reabilitada, com o motivo registrado pela organização (#178)

### Alterado

- **Mudança de regra:** envio fora do prazo não entra mais sozinho em
  avaliação, ranking, vitrine e votação; fica fora até o organizador aceitar.
  Depois de aceitar, recalcule os resultados e confira a distribuição de
  jurados (#174)
- A fila do jurado esconde, e a avaliação passa a negar acesso a, projetos que
  não concorrem mais (equipe desclassificada, atraso recusado) (#176)
- Certificado de participação segue por presença no evento: membro de equipe
  desclassificada continua elegível; colocação sai do ranking (regra
  registrada no PLANO.md) (#178)

### Corrigido

- A nota era calculada em `float` e errava um centavo em casos como 2,78 e
  4,59 com o mesmo peso (3,685 virava 3,68); agora as contas são em centésimos
  inteiros com arredondamento exato, o que também estabiliza desempate e
  ranking (#172)
- A página pública de resultados continuava mostrando a linha antiga de quem
  saiu do ranking (desclassificada, atraso recusado depois de aceito), porque
  o cálculo nunca apagava linhas antigas (#176)
- Voto popular na própria equipe passou a ser barrado, com mensagem em
  português (#172)
- Lançamento manual e importação de CSV aceitavam horário de entrega no
  futuro, fazendo qualquer envio contar como no prazo; agora são recusados, e
  o lançamento fora do formulário passa a ficar no activity log (#172)
- Escopo de evento: remover ou reatribuir jurado e remover conflito de
  interesse de edição anterior retornam 404, o check-in filtra o checkpoint
  por evento e a atribuição manual filtra a submissão (#172)

### Documentação

- `security.md` registra a exceção consciente do avatar no disco público;
  PLANO.md ganha a regra do envio atrasado e da desclassificação (#176, #178)

## [1.2.3] - 2026-10-10

Correções de uma auditoria de segurança sobre login com Google, seeders,
trilha de auditoria e exportação CSV, mais atualização de dependências e o
ambiente de homologação.

### Corrigido

- Login com Google quebrado em produção: no Railway não existe `.env`, então
  `GOOGLE_REDIRECT_URI="${APP_URL}/..."` chegava como texto literal e o Google
  recusava. A variável passou a levar a URL completa por ambiente, e o cliente
  OAuth ficou num projeto próprio do Google Cloud; `.env.example` e
  `docs/google-oauth.md` documentam isso (#162)
- A tela piscava ao passar o mouse nos links do menu, porque o prefetch do
  Inertia disparava o efeito de esmaecer da navegação; agora prefetch é
  ignorado (#164)
- `DatabaseSeeder` e `DemoSeeder` criavam admin e organizador com senha
  `password` e não checavam o ambiente; agora recusam rodar em produção, e o
  runbook manda rodar só o `RoleSeeder` lá (#170)
- Mudança de prazo de submissão, janela de voto ou status do evento não
  deixava rastro; agora vai para o activity log com autor e valores
  antes/depois (#170)
- Recalcular o resultado depois de publicado alterava o pódio público sem
  registro, e republicar sobrescrevia a data da primeira publicação; agora o
  recálculo é logado com o ranking antes/depois e a data original é mantida
  (#170)
- Remover ou reatribuir jurado apagava a nota já enviada sem rastro; agora a
  Policy nega quando a avaliação foi enviada (o caminho é reabrir com motivo),
  a remoção permitida é logada e a tela esconde os botões nesse caso (#170)
- CSV exportado aceitava injeção de fórmula do Excel em nome de equipe, título
  e links; campos de texto livre que começam com `=`, `+`, `-` ou `@` ganham um
  apóstrofo (#170)
- Entrada em equipe por código de convite passou a ter limite de 10 tentativas
  por minuto (#170)

### Alterado

- Dependências atualizadas: Laravel 12.65→12.69.3, league/commonmark,
  league/flysystem, phpseclib e transitivas; `composer audit` e `npm audit`
  sem avisos (#168)

### Infra e processo

- Ambiente de homologação no Railway (branch `homologacao`, banco e Redis
  próprios, `DemoSeeder`), com CI em push na `homologacao`, workflow
  `sync-homologacao.yml` que traz a `main` por merge a cada push e
  `environment_url` do deployment por ambiente; fluxo dev → homologação →
  produção documentado em `deploy/railway.md` §8 e `CLAUDE.md`; `ARG
  INSTALL_DEV` no Dockerfile (#166)

### Documentação

- README com links do protótipo navegável do Figma (43 telas), do arquivo de
  design e dos ambientes de produção e homologação (#156, #158, #160, #166)

## [1.2.2] - 2026-09-24

Correções de uma auditoria de segurança sobre publicação de resultado e
voto popular.

### Corrigido

- `PublishResults` não gravava no activity log — a publicação de resultado,
  ação mais sensível do sistema, ficava sem rastro de autoria exigido pela
  regra de auditoria (#147)
- Voto popular não checava o status da submissão, só o evento — participante
  autenticado podia votar em submissão rascunho ou desclassificada
  adivinhando um id sequencial; agora reusa o mesmo filtro
  `countsForEvaluation()` da vitrine pública (#147)

## [1.2.1] - 2026-09-17

Correção pontual sobre o crachá digital do participante.

### Corrigido

- Crachá digital mostrava "Participante" fixo para todo usuário, mesmo
  organizador, jurado ou admin — o rótulo agora vem de `Role::label()`
  calculado a partir dos papéis reais do usuário (#146)

## [1.2.0] - 2026-09-09

Fecha uma segunda rodada de ajustes sobre a repaginação da 1.1.0: uma nova
regra de papéis separa de vez participante de jurado/organizador/admin, a
identidade institucional do IFPR chega ao certificado em PDF e aos e-mails
transacionais, e mais uma passada visual completa pela interface — guiada
por referências do 21st.dev — cobre público, autenticação, admin,
participante, jurado e configurações.

### Adicionado

- Regra de negócio: participante fica incompatível com papel privilegiado
  (jurado, organizador, admin) — promover um participante existente remove
  `participante` automaticamente e registra a remoção em auditoria; tentar
  adicionar `participante` a quem já é privilegiado é recusado. Papéis
  privilegiados continuam podendo acumular entre si, e nenhum registro
  histórico é apagado (#143)
- Comando de manutenção `hackathon:fix-participant-role-conflicts`, dry-run
  por padrão, para corrigir dado de produção que viole essa regra (#143)
- Saudação pessoal ("Olá, {nome}.") no início de organizador e jurado, no
  mesmo tom do dashboard do participante (#144)

### Alterado

- `/dashboard` deixa de redirecionar staff e jurado para fora da rota —
  agora renderiza o card certo pra cada papel ali mesmo, e "Início" na
  sidebar passa a destacar de verdade em vez de trocar de URL a cada
  clique (#144)
- Sidebar esconde "Minha equipe" e "Meu projeto" para quem não é
  participante; crachá e certificados continuam visíveis para todos os
  papéis (#143)
- Certificado em PDF ganha novo modelo institucional em paisagem A4 (faixa
  lateral verde, papel marfim, moldura com cantos dourados, tipografia
  serifada), corrigindo de quebra a segunda página quase em branco que
  aparecia em certificados com texto mais longo (#139)
- Templates de e-mail transacional (Laravel Markdown Mail) ganham
  identidade institucional do IFPR: logo no cabeçalho em vez do prompt de
  terminal, layout de 600px, botão de ação alinhado à esquerda e cores com
  mais contraste (#145)
- Segunda passada visual em toda a interface — pública, autenticada,
  admin, jurado e configurações — guiada por referências do 21st.dev;
  fluxos Inertia, props e regras de domínio preservados, mudança só de
  estilo (#142)

## [1.1.0] - 2026-09-08

Fecha duas das quatro decisões organizacionais que ficaram em aberto na
1.0.0: hospedagem fora do Brasil (autorizada pela orientadora) e provedor
de e-mail em produção, ambas em 2026-08-22
([#71](https://github.com/joaopedroplinta/hackathon-ifpr/issues/71),
[#78](https://github.com/joaopedroplinta/hackathon-ifpr/issues/78)).
Restam só a nomeação formal do DPO
([#83](https://github.com/joaopedroplinta/hackathon-ifpr/issues/83)) e o
ensaio ao vivo com a equipe de organização
([#86](https://github.com/joaopedroplinta/hackathon-ifpr/issues/86)), nenhuma
delas código. O grosso desta versão é a repaginada completa da interface
(público, participante, organizador e jurado) e a personalização do
certificado em PDF.

### Adicionado

- Tela de administração para conceder e revogar papéis de usuário (#111)
- Requisitos de senha exibidos como checklist ao vivo durante o cadastro
  (#112)
- Template de e-mail transacional redesenhado com a identidade visual da
  marca, em vez do template padrão do Laravel (#117)
- Certificado em PDF passa a trazer CPF, matrícula e equipe/projeto de quem
  recebe (#121), bloco de assinatura com nome e cargo (#123), e agora a
  organização pode personalizar logo e cor de destaque por evento (#132)
- Tela de criação do primeiro evento, com anexo do regulamento no mesmo
  fluxo (#125, #128)
- Repaginada completa da interface: página pública (Lote A) e componentes
  compartilhados (`ContainerPagina`, `EstadoVazio`, `Status`,
  `ConfirmarAcao` e outros em `components/hackathon/`), experiência do
  evento público, fluxos autenticados (conta, equipe, submissão, jurado) e
  novo ícone do sistema ("Encontro", substituindo o prompt de terminal
  anterior) (#136, #138)
- Organizador pode habilitar ou desabilitar, por evento, a coleta de
  tamanho de camiseta e restrição alimentar na inscrição — desligado por
  padrão, para não pedir dado sem finalidade (#138)
- Dashboard do participante passa a lembrar de completar o perfil
  institucional antes de emitir certificado (#138)

### Alterado

- Rotas do organizador reorganizadas: prefixo `/admin` separado das rotas
  de staff, que passam a viver sob `/painel` (#134)
- PHP de produção rebaixado de 8.5 para 8.4 — a imagem `php:8.5-cli-alpine`
  tinha um bug de extensão que impedia instalar o opcache; no 8.4 o
  opcache volta a funcionar e ficou reativado ([#137](https://github.com/joaopedroplinta/hackathon-ifpr/pull/137))
- Armazenamento de upload migrado para Railway Volume, resolvendo a
  persistência entre deploys que antes dependia do disco efêmero do
  container (#131)

### Corrigido

- Notificação de verificação de e-mail agora vai pela fila em vez de
  bloquear a requisição, e o conteúdo do e-mail passou a ser
  inteiramente em português (#113, #115)
- E-mail de redefinição de senha e a mensagem da tela "esqueci minha
  senha" estavam em inglês (template padrão do `Illuminate\Auth\
  Notifications\ResetPassword`, nunca customizado) — agora totalmente em
  português e sem revelar se a conta existe (#138)
- Healthcheck do worker/scheduler não podia ficar no `railway.json`
  compartilhado com o serviço web (#114)
- Verde residual da identidade anterior ao redesenho ainda aparecia no
  favicon e em gráficos (#119)
- Tela em branco (404 cru) quando não havia evento publicado, trocada por
  uma tela de orientação (#127); texto de referência interna que vazava
  pra tela de usuários sem contexto pro leitor (#129); link de logout que
  faltava no cabeçalho das páginas públicas (#126)

## [1.0.0] - 2026-08-21

Fecha o desenvolvimento de funcionalidades: todas as fases do evento
(inscrição, agenda, submissão, avaliação, resultado, certificado) funcionam
de ponta a ponta, com identidade visual definitiva. O que resta no projeto
inteiro são quatro decisões organizacionais, nenhuma delas código —
hospedagem com dado no Brasil ([#71](https://github.com/joaopedroplinta/hackathon-ifpr/issues/71)),
provedor de e-mail em produção ([#78](https://github.com/joaopedroplinta/hackathon-ifpr/issues/78)),
nomeação formal do DPO ([#83](https://github.com/joaopedroplinta/hackathon-ifpr/issues/83)) e
ensaio ao vivo com a equipe de organização ([#86](https://github.com/joaopedroplinta/hackathon-ifpr/issues/86)).

### Adicionado

- Página pública para navegar edições anteriores do hackathon e seus
  resultados publicados (#98)
- `DemoSeeder`: evento encerrado com resultado publicado + evento atual com
  inscrições abertas, para demonstrar o sistema fora do ambiente de ensaio
  (#96)
- Identidade visual reconstruída do zero: o conceito "terminal" original
  (painel de log, prompt `$ `, badges `[status]`) reprovou num pente-fino
  de UX por ler como template genérico de IA. No lugar, estilo
  neutro/minimalista com acento de cor só em detalhe (link, anel de foco) —
  ver `PLANO.md` §11 para a paleta e o conceito de layout novos (#100)
- Identidade institucional: CPF com validação real de dígito verificador
  (`App\Rules\CpfValido`, não só formato), vínculo institucional (aluno
  IFPR / professor IFPR / externo) com matrícula SUAP ou SIAPE condicional
  ao vínculo, e troca de foto de perfil (#101, #102, PR #103)
- Requisitos de senha (letra maiúscula, minúscula, símbolo, 8+ caracteres)
  agora aplicados no servidor e exibidos no formulário — antes só exigia 8
  caracteres, sem nenhum aviso (PR #103)
- Documentação técnica: diagrama de entidade-relacionamento, diagrama de
  classes, diagrama de casos de uso e diagramas de sequência dos 5 fluxos
  principais (`docs/diagramas.md`, PR #104)
- Deploy de demonstração migrado de Render + Supabase pro Railway
  (`railway.json`, roteiro em `deploy/railway.md`), agora com worker de
  fila e agendador (`schedule:run`) de verdade em vez do `QUEUE_CONNECTION=
  sync` improvisado do free tier da Render. O dado de ensaio saiu do Brasil
  por completo nessa troca — ver aviso no topo de `deploy/railway.md`
- Licença MIT e README reescrito (PR #108)

### Alterado

- Identidade visual trocada de novo: o estilo neutro/minimalista de #100
  leu como vazio e genérico demais na avaliação de quem organiza o
  evento. No lugar, um sistema "SaaS denso" — cards com borda de verdade,
  hierarquia tipográfica forte, cor institucional como cor primária — em
  público, autenticação, participante, jurado e organizador. O hero da
  home pública passou por três conceitos (peça 3D em Three.js, ilustração
  estática em SVG, nenhuma ilustração) antes de fechar num layout
  centralizado sem decoração, com estatísticas reais do evento vindas do
  banco em vez de número inventado (PR #108)

### Corrigido

- `auth.user.avatar` nunca funcionava — a coluna real é `avatar_url` —
  então nenhuma foto de perfil, nem a vinda do Google, jamais aparecia no
  header ou na sidebar (PR #103)
- 17 `judge_assignments` de um script de carga antigo apontavam para um
  `judge_id` que nunca existiu de verdade, derrubando `/admin/jurados` com
  erro 500 — dado órfão limpo diretamente; a FK `restrictOnDelete()` já
  impede que isso aconteça de novo por um caminho normal do app

## [0.7.0] - 2026-08-18

Infraestrutura de demonstração — sem mudança visível pro usuário final.

### Adicionado

- Deploy de demonstração via Render + Supabase (`render.yaml`, `Dockerfile`,
  roteiro em `deploy/render-supabase.md`), para mostrar o sistema fora do
  `localhost`. **Não é** a decisão de hospedagem do evento real — dado fora
  do Brasil, só dado de ensaio ali dentro (ver Épico 11.2 do backlog)

### Corrigido

- Título da aba mostrava "Início - Laravel" em vez do nome do projeto no
  build de produção via Docker (`VITE_APP_NAME` não chegava no estágio de
  build dos assets)
- URLs de asset geradas em `http://` atrás do proxy reverso da Render
- Logs de exceção iam para lugar nenhum que o visualizador da Render
  enxergasse — agora vão para stderr
- Migration deixava de rodar sozinha no boot do container (free tier não
  dá Shell nem One-Off Jobs para rodar à mão)

## [0.6.0] - 2026-08-17

Fecha as semanas 0 a 6 do cronograma: fluxo de ponta a ponta do participante
e do organizador, do cadastro ao resultado publicado.

### Adicionado

- Autenticação por Google (Socialite, domínio institucional) e e-mail/senha
  com verificação
- Equipes: criar, convidar por e-mail e código, entrar, gerenciar, travas de
  tamanho e prazo
- Submissões com upload, versionamento e deadline verificado no servidor
- Agenda pública e do organizador, com exportação `.ics`
- Check-in por QR Code (leitor de câmera com fallback manual)
- Rubrica configurável e atribuição de jurados com checagem de conflito
- Painel do jurado com autosave, pensado para avaliação pelo celular
- Cálculo de resultados (`hackathon:compute-results`), publicação
  controlada, página pública de resultados e voto popular
- Certificados em PDF (participação, jurado, organização, colocação) com
  emissão manual e validação pública por código (`/validar/{code}`)
- Notificações por e-mail em fila: lembrete de prazo de submissão e
  resultado publicado
- Banner de consentimento de cookies (LGPD)
- Troca do driver de filas de `database` para Redis, com persistência e
  systemd configurados
- Página de privacidade LGPD, RoPA e minuta de designação do DPO
- Número de versão exibido no rodapé da sidebar (`v0.6.0` em produção,
  `v0.6.0-dev+<commit>` fora dela)

[Não lançado]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.3.4...HEAD
[1.3.4]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.3.3...v1.3.4
[1.3.3]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.3.2...v1.3.3
[1.3.2]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.3.1...v1.3.2
[1.3.1]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.2.3...v1.3.0
[1.2.3]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.2.2...v1.2.3
[1.2.2]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.2.1...v1.2.2
[1.2.1]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v0.7.0...v1.0.0
[0.7.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/joaopedroplinta/hackathon-ifpr/releases/tag/v0.6.0
