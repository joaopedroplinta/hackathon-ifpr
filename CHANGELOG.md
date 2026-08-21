# Changelog

Mudanças notáveis deste projeto, por versão. Formato baseado em
[Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/); o projeto segue
[SemVer](https://semver.org/lang/pt-BR/) — enquanto a major for `0`, um
minor pode trazer mudança que quebra compatibilidade.

A versão exibida no rodapé da sidebar vem de `composer.json` (`version`);
bump manual a cada tag.

## [Não lançado]

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

[Não lançado]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v0.7.0...v1.0.0
[0.7.0]: https://github.com/joaopedroplinta/hackathon-ifpr/compare/v0.6.0...v0.7.0
[0.6.0]: https://github.com/joaopedroplinta/hackathon-ifpr/releases/tag/v0.6.0
