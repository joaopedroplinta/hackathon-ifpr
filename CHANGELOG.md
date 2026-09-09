# Changelog

Mudanças notáveis deste projeto, por versão. Formato baseado em
[Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/); o projeto segue
[SemVer](https://semver.org/lang/pt-BR/) — enquanto a major for `0`, um
minor pode trazer mudança que quebra compatibilidade.

A versão exibida no rodapé da sidebar vem de `composer.json` (`version`);
bump manual a cada tag.

## [Não lançado]

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

[Não lançado]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v0.7.0...v1.0.0
[0.7.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/joaopedroplinta/hackathon-ifpr/releases/tag/v0.6.0
