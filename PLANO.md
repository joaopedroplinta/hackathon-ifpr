# Sistema de Apoio ao 1º Hackathon — IFPR Pinhais

Plano de projeto. Documento vivo: atualizar conforme decisões mudarem.

---

## 1. Objetivo

Sistema web único que cobre o ciclo completo do evento:

1. **Antes** — inscrição, formação de equipes, agenda pública
2. **Durante** — check-in, submissão de projetos, acompanhamento da agenda
3. **Depois** — avaliação pelos jurados, resultados públicos, certificados

Requisito transversal: o sistema deve sobreviver ao dia do evento. Rede instável,
50 pessoas submetendo no último minuto e jurado avaliando pelo celular são o
cenário normal, não a exceção.

---

## 2. Stack

| Camada | Escolha | Motivo |
|---|---|---|
| Backend | Laravel (última estável — confirmar versão no `laravel new`) | Ecossistema completo: auth, filas, notificações, storage |
| Ponte | Inertia v2 | React sem escrever API REST, sem CORS, sem gerenciar token |
| Frontend | React + TypeScript | Tipagem nas props vindas do controller |
| Estilo | Tailwind v4 + shadcn/ui | Starter kit oficial já entrega isso configurado |
| Banco | PostgreSQL 17 (Docker) | Mesmo banco em dev e prod — sem surpresa de tipo no deploy |
| Filas | Driver `database` | E-mails e PDFs fora do request. Redis só se sobrar tempo |
| Testes | Pest | Sintaxe enxuta, feature tests cobrem o fluxo real |

### Pacotes

```
laravel/socialite                 # Google OAuth
spatie/laravel-permission         # papéis e permissões
barryvdh/laravel-dompdf           # certificados em PDF (PHP puro, sem Chromium)
bacon/bacon-qr-code               # QR em SVG, sem dependência de imagick
spatie/laravel-activitylog        # auditoria de notas e mudanças sensíveis
```

Comando de criação:

```bash
laravel new hackathon --react     # starter kit React = Inertia v2 + TS + Tailwind
```

### Ambiente

`docker-compose.yml` com Postgres 17 e Mailpit (captura e-mails em dev, sem
mandar nada pro mundo real). PHP, Composer e Node rodam na máquina — já estão
instalados e é mais rápido que containerizar tudo.

---

## 3. Papéis

| Papel | Pode |
|---|---|
| **guest** | Ver agenda, resultados publicados e páginas públicas de projeto |
| **participante** | Criar/entrar em equipe, submeter projeto, votar no prêmio popular, baixar certificado |
| **jurado** | Ver só as submissões atribuídas a ele, avaliar, comentar |
| **organizador** | CRUD de evento/agenda/equipes, check-in, atribuir jurados, publicar resultados |
| **admin** | Tudo + gerenciar usuários e papéis |

Papéis acumulam: um monitor pode ser participante e organizador. Por isso
`spatie/laravel-permission` (tabela pivot) e não uma coluna `role` no usuário.

Toda regra de acesso vive em **Policies**, nunca espalhada em `if` no controller.

---

## 4. Modelo de dados

### Evento e estrutura

```
events
  id, name, slug, edition, status
  description                 # o tema/desafio da edição, texto livre por ora
  registration_opens_at, registration_closes_at
  starts_at, ends_at
  submission_deadline
  voting_opens_at, voting_closes_at
  results_published_at        # NULL = resultados escondidos
  min_team_size, max_team_size

tracks                        # trilhas/categorias
  id, event_id, name, description, color
```

**Tema x trilha — não confundir.** O tema (`events.description`) é o desafio
geral da edição: o problema específico que toda equipe precisa resolver com
uma solução inovadora (ex.: "mobilidade urbana", "acesso à saúde"). É o que
justifica a inscrição — sem ele, o hackathon não tem norte. Trilha (`tracks`)
é a categoria da solução dentro desse tema (Saúde, Educação, Cidade
Inteligente), usada pra agrupar submissões e gerar um pódio por categoria.
Um evento tem um tema só; pode ter várias trilhas.

Leitura de apoio sobre organização de hackathon (etapas, papel do desafio,
formato competitivo x colaborativo):
<https://liga.ventures/insights/artigos/entenda-o-que-e-e-como-organizar-um-hackathon/>

`event_id` em tudo desde o começo. Custa uma coluna agora e permite a 2ª edição
sem migração dolorosa depois.

### Pessoas e equipes

```
users
  id, name, email, password (nullable — só Google), google_id (nullable)
  avatar_url, curso, telefone, qr_token (uuid, único), email_verified_at

event_registrations           # inscrição no evento específico
  id, event_id, user_id, registered_at, dietary_notes, shirt_size

teams
  id, event_id, track_id, name, slug, invite_code (único)
  description, status (draft|confirmed|disqualified), leader_id

team_members
  id, team_id, user_id, role (leader|member)
  status (invited|active|left), joined_at

team_invites                  # convidar quem ainda não tem conta
  id, team_id, email, token, expires_at, accepted_at
```

**Regras:**
- Um usuário está em no máximo 1 equipe por evento (unique composto)
- Equipe só vira `confirmed` com o mínimo de membros
- Depois de `registration_closes_at`, entrada/saída de membro só via organizador
- Líder que sai transfere a liderança antes — sem equipe órfã

### Submissões

```
submissions
  id, team_id, title, summary, description
  repo_url, video_url, deploy_url
  status (draft|submitted|late|disqualified)
  submitted_at, current_version
  source (web|form|email|manual)     # por onde entrou — ver Anexo A
  recorded_by (nullable)             # organizador que lançou, se manual
  original_submitted_at (nullable)   # horário real comprovado

submission_versions           # histórico completo
  id, submission_id, version, payload (json), created_by, created_at

submission_files
  id, submission_id, version, path, original_name, mime, size
```

**Regras:**
- Cada envio gera uma versão nova — nada é sobrescrito
- Deadline validado **no servidor** com `now()`, comparando contra
  `event.submission_deadline`. O contador no front é enfeite
- Envio após o prazo entra como `late` e fica visível pro organizador decidir,
  em vez de ser rejeitado em silêncio
- Upload: limite explícito (ex. 25 MB), tipos permitidos em allowlist,
  nome de arquivo gerado pelo sistema

### Avaliação

```
rubrics
  id, event_id, name, is_active

criteria
  id, rubric_id, name, description, weight, max_score, position

judge_assignments
  id, event_id, judge_id, submission_id
  status (pending|in_progress|done), assigned_at

evaluations
  id, assignment_id, status (draft|submitted)
  overall_comment, submitted_at

evaluation_scores
  id, evaluation_id, criterion_id, score, comment

conflicts_of_interest         # jurado não avalia equipe onde tem vínculo
  id, judge_id, team_id, reason
```

**Regras:**
- Nota salva como rascunho a cada mudança (autosave) — jurado não perde trabalho
  se a rede cair
- `evaluations` só conta pro resultado com `status = submitted`
- Alteração de nota já submetida exige justificativa e fica no activity log
- Atribuição automática distribui N jurados por submissão, respeitando conflitos
  e balanceando carga; organizador pode ajustar na mão

### Resultados

```
results                       # materializado, não calculado on-the-fly
  id, event_id, submission_id
  final_score, criteria_breakdown (json)
  rank_overall, rank_track
  popular_votes_count
  computed_at
```

Comando `php artisan hackathon:compute-results {event}` recalcula. Vantagens:
o ranking fica congelado e auditável, e a página pública não faz agregação pesada.

**Fórmula:** média ponderada dos critérios dentro de cada avaliação →
média simples entre os jurados daquela submissão.

**Desempate (definir por escrito ANTES do evento):** maior nota no critério de
maior peso → maior nota no segundo critério → menor tempo de submissão.

**Normalização entre jurados:** *não entra na v1.* Z-score é justo na teoria e
impossível de explicar pra equipe que perdeu. Se houver dispersão grande entre
jurados, o organizador vê no painel e resolve conversando.

### Agenda

```
schedule_items
  id, event_id, track_id (nullable)
  title, description, type (palestra|workshop|checkpoint|refeicao|deadline)
  starts_at, ends_at, location, speaker_name, speaker_bio
  is_published
```

Página pública com "acontecendo agora" destacado, timeline por dia e exportação
`.ics` (agenda no celular de quem quiser).

### Check-in

```
checkpoints
  id, event_id, name, type (entrada|dia|oficina), starts_at, ends_at

attendances
  id, checkpoint_id, user_id, checked_in_at
  checked_by, method (qr|manual)
```

**Fluxo:** cada participante tem `qr_token` no crachá/celular. O organizador
escaneia com o próprio aparelho, abre `/checkin/{token}?checkpoint=X`, confirma.
Controle fica com a organização — participante não faz auto check-in.

Tem que existir **check-in manual por busca de nome**. QR falha, celular
descarrega, crachá some.

### Voto popular

```
popular_votes
  id, event_id, submission_id, user_id, created_at
  unique (event_id, user_id)      # 1 voto por pessoa por evento
```

Só usuário autenticado e inscrito vota. Janela definida em
`voting_opens_at/closes_at`. Contagem escondida durante a votação — evita efeito
manada. Prêmio separado, não soma na nota técnica.

### Certificados

```
certificates
  id, event_id, user_id
  type (participacao|colocacao|jurado|mentor|organizador)
  code (uuid curto, único), payload (json), issued_at
```

PDF gerado em fila. Página pública `/validar/{code}` mostra nome, tipo, evento
e carga horária. Carga horária vem das presenças registradas.

---

## 5. Telas

### Público
- `/` — landing: contagem regressiva, o que é, como participar
- `/agenda` — timeline + "acontecendo agora" + download `.ics`
- `/projetos` — vitrine das submissões (só após publicação)
- `/projetos/{slug}` — página do projeto: descrição, repo, vídeo, equipe
- `/resultados` — pódio geral, pódio por trilha, prêmio popular
- `/validar/{code}` — validação de certificado

### Participante
- `/dashboard` — próximo item da agenda, status da equipe, deadline, pendências
- `/equipe` — criar, entrar por código, gerenciar membros, sair
- `/equipe/convites` — convidar por e-mail, link com código
- `/submissao` — formulário, upload, histórico de versões, contador do deadline
- `/certificados` — download

### Jurado
- `/jurado` — fila de avaliação, progresso (7 de 12 feitas)
- `/jurado/avaliar/{submission}` — projeto de um lado, rubrica do outro;
  autosave; navegação "próxima pendente"

### Organizador
- `/admin` — painel: inscritos, equipes sem submissão, jurados atrasados,
  presença do dia
- `/admin/evento` — nome, tema/desafio, datas, limites, abrir/fechar fases.
  **Pendente**: nunca foi implementada em nenhuma sprint (semanas 0–6). Hoje
  esses campos só existem via seed/tinker — a organização não tem como editar
  nada disso pela interface. Tema precisa aparecer também na landing pública
  e/ou inscrição, é o que atrai o participante.
- `/admin/equipes` — listar, editar, desqualificar, forçar membro
- `/admin/submissoes` — todas, filtro por trilha/status, download em lote
- `/admin/rubrica` — critérios, pesos, escala
- `/admin/jurados` — convidar, conflitos, distribuição automática, ajuste manual
- `/admin/agenda` — CRUD
- `/admin/checkin` — scanner QR + busca manual
- `/admin/resultados` — recalcular, conferir, **publicar** (ação explícita)

---

## 6. Cronograma — 8 semanas

Sprint = 1 semana. Se o prazo for 1 mês, corte as semanas 6 e 7 (voto popular,
certificados) e faça notificações apenas nos 3 marcos críticos.

| Semana | Entrega | Pronto quando |
|---|---|---|
| **0** | Setup: `laravel new --react`, docker-compose, Pint + ESLint, migrations base, papéis, layout e navegação | Login do starter kit funciona, `php artisan test` verde |
| **1** | Auth completa: Google (Socialite, restrito a domínio institucional), e-mail/senha com verificação, perfil, inscrição no evento | Dá pra criar conta pelos dois caminhos e se inscrever |
| **2** | Equipes: criar, convidar por e-mail e código, entrar, gerenciar, travas de tamanho e prazo | Duas contas formam equipe do zero |
| **3** | Submissões: formulário, upload, versionamento, deadline no servidor, painel do organizador | Envio após o prazo cai como `late`, testado |
| **4** | Agenda (público + admin + `.ics`) e check-in QR com fallback manual | Escanear crachá registra presença |
| **5** | Rubrica configurável, atribuição de jurados com conflito, painel do jurado com autosave | Jurado avalia 3 projetos pelo celular sem perder nota |
| **6** | Cálculo de resultados, publicação controlada, página pública, voto popular | `hackathon:compute-results` bate com planilha conferida na mão |
| **7** | Certificados em PDF + validação, notificações por e-mail em fila, painel do organizador completo | E-mail de deadline dispara sozinho no horário |
| **8** | **Ensaio geral**: seed com 30 equipes e 5 jurados, teste de carga, acessibilidade, responsivo, deploy, plano B | Simulação completa do evento sem intervenção manual |

A semana 8 não é opcional. É onde os problemas reais aparecem.

---

## 7. Decisões travadas

| Questão | Decisão |
|---|---|
| React ↔ Laravel | Inertia v2. Um repo, um deploy, sem API REST |
| Login | Google (Socialite) + e-mail/senha. Jurado externo usa senha |
| Banco | Postgres em dev e prod. Sem SQLite |
| Normalização de notas | Não entra. Complexidade sem ganho defensável |
| Publicação de resultado | Ação manual e explícita do organizador (`results_published_at`) |
| Deadline | Sempre `now()` no servidor. Front só exibe |
| Idioma | Interface e rotas em português. Código, migrations e commits em inglês |
| Fuso | Tudo UTC no banco, `America/Sao_Paulo` na exibição |

---

## 8. Riscos e plano B

| Risco | Mitigação |
|---|---|
| Internet cai no dia | Página de submissão leve. Canal alternativo definido (formulário/e-mail) e regra de como o organizador insere manualmente |
| Todo mundo submete no último minuto | Fila pra processamento pesado. Upload direto pro disco, sem transformação síncrona |
| Jurado não aparece | Reatribuição em 1 clique no painel. Cálculo tolera número variável de jurados por submissão |
| Equipe perde o trabalho no formulário | Autosave de rascunho + versionamento |
| Disputa sobre nota | Activity log de toda alteração + rubrica pública desde o início |
| Arquivo enorme ou malicioso | Allowlist de tipo, limite de tamanho, nome gerado pelo sistema, storage fora do webroot |
| Deploy quebrar na véspera | Congelar código 48h antes. Só correção crítica |

---

## 9. Definição de pronto

Feature só está pronta com:

- [ ] Policy cobrindo quem pode fazer o quê
- [ ] Validação em Form Request (nunca só no front)
- [ ] Feature test do caminho feliz + pelo menos 1 caminho de erro
- [ ] Funciona em tela de celular
- [ ] Estados de vazio, carregando e erro tratados na UI
- [ ] Textos em português, sem string técnica vazando pro usuário

---

## 10. Próximos passos

1. Rodar o setup da semana 0
2. Escrever as migrations do núcleo (event, team, submission)
3. Seeder com dados realistas — trabalhar com banco vazio esconde problema

---

## 11. Identidade visual

O sistema nasceu com a aparência padrão do starter kit: ícone do Laravel no
favicon e no header, paleta cinza neutra do shadcn. Esta seção documenta uma
identidade própria do evento, puxando pro verde institucional do IFPR, antes
de espalhar a mudança pelo código.

### Ponto de partida

Verde institucional do IFPR, **Pantone 362 C** (`#509E2F`), extraído do manual
oficial de aplicação da marca:
<https://ifpr.edu.br/wp-content/uploads/2016/04/manual-aplicacao-marca-ifpr.pdf>.

A cor do manual é a referência — a interface usa uma versão ajustada pra
contraste de acessibilidade (tabela abaixo). **O símbolo institucional do IF
não é reaproveitado**: uso da marca oficial é regulado pelo manual, então o
evento tem um símbolo próprio, só compartilhando a família de verde.

### Paleta

| Token | Hex | Uso |
|---|---|---|
| `verde-ifpr` | `#3F8F2E` | Cor primária — botões, links, foco. Pantone 362C escurecido pra dar contraste AA com texto branco |
| `verde-mata` | `#163C1B` | Fundo do painel "terminal" (hero) e base do modo escuro |
| `verde-brilho` | `#8FD14F` | Destaque, sucesso, linha "adicionada" no terminal, gráfico |
| `grafite` | `#1B1B1D` | Texto e superfícies escuras — troca o cinza neutro do shadcn |
| `papel` | `#F5F7F3` | Fundo claro — branco com leve viés esverdeado, não o creme genérico de template de IA |
| `amarelo-sinal` | `#E8B93F` | Urgência — prazo perto do fim, aviso. Usado só em pontos de sinal, não decoração |

Gráficos (`chart-1..5` do shadcn) usam a rampa: `verde-ifpr`, `verde-brilho`,
`amarelo-sinal`, `verde-mata`, e um cinza quente `#6B6E68` pra série neutra —
sem introduzir matiz fora da paleta.

Modo escuro: fundo `#14170F` (grafite com viés verde, não cinza puro),
texto `#F5F7F3`, primária clareada pra `#5CB84A` pra manter contraste em
fundo escuro.

### Tipografia

| Papel | Fonte | Por quê |
|---|---|---|
| Título/display | Space Grotesk | Geométrica, caráter técnico, números desenhados pra se destacar (contagem regressiva de prazo) |
| Texto/corpo | Public Sans | Fonte do padrão de serviços digitais públicos (USWDS) — reforça "instituição pública", ótima legibilidade em PT-BR |
| Dado/utilitário | JetBrains Mono | Prazos, código de convite, `qr_token`, status — qualquer lugar que hoje mostra um dado bruto |

Servidas via Bunny Fonts, mesmo provedor já usado pro Instrument Sans atual —
sem cookie/tracking do Google Fonts.

### Conceito de layout — "log de build"

A landing pública ganha um painel estilo terminal como elemento de
assinatura: um log mostrando o fluxo real do evento (equipe formada → projeto
enviado → avaliado) em JetBrains Mono, com timestamp e cursor piscando. É o
único momento visualmente ousado da interface — o resto (formulários, tabelas
do admin, painel do jurado) fica disciplinado, herdando só paleta e
tipografia, sem repetir o efeito.

Rótulos de seção usam prefixo de prompt (`$ como_participar`) em vez de
uppercase genérico — só faz sentido porque o produto inteiro já fala a
língua de terminal/versionamento (deadline, commit, log). Badges de status
(`Enviado`, `Pendente`, `Desqualificado`) passam a render em `JetBrains Mono`
com colchete (`[enviado]`), o mesmo vocabulário aplicado no sistema inteiro,
não só na landing.

### Ícone e favicon

`AppLogoIcon` (hoje o "L" do Laravel) é substituído por um símbolo próprio:
prompt de terminal (`>_`) num quadrado arredondado em `verde-ifpr`. Simples o
bastante pra continuar legível em 16px de favicon.

### Onde mexeu

- `resources/css/app.css` — tokens de cor claro/escuro e import de fonte
- `components/app-logo-icon.tsx` (prompt `>_`), `public/favicon.svg`
- `components/hackathon/cabecalho-publico.tsx`, `components/hackathon/log-de-build.tsx`
  (painel de assinatura), `pages/publico/*.tsx`
- Badges de status em `pages/admin/{submissoes,rubrica,agenda}/*.tsx` e
  `pages/publico/agenda.tsx` — `[enviado]` em JetBrains Mono

**Implementado.** Aplicado em todo o admin (herda os tokens globais sem
mexer arquivo por arquivo) e nas páginas públicas listadas acima.

---

# Anexo A — Plano B

Premissa: **no deadline, o que importa é carimbar o tempo e guardar o link do
código.** Deck, vídeo e descrição bonita podem ser completados depois. Todo o
plano B otimiza pra preservar essas duas coisas.

## A.1 O que pode falhar

| # | Falha | Alcance | Probabilidade |
|---|---|---|---|
| 1 | Internet do campus cai | Todos | Média |
| 2 | App/servidor fora do ar | Todos | Baixa, alto impacto |
| 3 | Wi-fi ruim ou notebook da equipe morre | 1–3 equipes | **Alta** |
| 4 | Upload de arquivo grande falha, resto funciona | Algumas | Alta |
| 5 | Painel do jurado cai durante a avaliação | Jurados | Baixa |

O caso 3 é o que mais acontece e é o mais fácil de resolver — e o que costuma
ser esquecido porque todo mundo planeja pro apocalipse do caso 1.

## A.2 Escada de degradação

Cada degrau só entra quando o anterior falha. Ordem definida **antes** do evento
e impressa.

**Degrau 0 — Git é a fonte de verdade do tempo**
Regra no regulamento, desde a abertura das inscrições:

> Vale o horário do último commit no repositório informado, feito até o prazo.
> A submissão no sistema pode ser regularizada depois pela organização.

Isso resolve sozinho a maior parte dos casos 1, 3 e 4. O timestamp do Git não
depende da nossa infraestrutura, é verificável e ninguém consegue forjar
convincentemente. **É o item mais barato e mais valioso do plano B inteiro.**

**Degrau 1 — Submissão mínima**
Se o upload falha mas o site abre: botão "enviar só o link do repositório".
Grava `status = submitted`, marca campos pendentes, libera complemento depois do
prazo. Uma tela, dois campos.

**Degrau 2 — Formulário externo**
Google Forms criado e testado **na semana 8**, hospedado fora da nossa infra.
Campos: equipe, e-mail do líder, URL do repo, hash do último commit.
Link curto impresso e colado nas paredes. Só é divulgado se a organização
declarar incidente.

**Degrau 3 — E-mail dedicado**
Endereço do evento. O horário de recebimento vira `original_submitted_at`.

**Degrau 4 — Papel**
Monitor com prancheta: equipe, repo, hora, assinatura do líder. Funciona sem
energia. Digitado depois com `source = manual`.

## A.3 Incidentes e tolerância

```
incidents
  id, event_id
  kind (rede|sistema|energia|outro)
  started_at, ended_at, description
  deadline_extension_minutes
  declared_by
```

Quando o organizador declara um incidente com extensão de prazo, o sistema
estende o deadline **para todas as equipes**, não caso a caso. Prazo esticado só
pra quem reclamou é o tipo de coisa que gera contestação legítima. A extensão
fica registrada com motivo e horário.

## A.4 Importação e regularização

```bash
php artisan hackathon:import-submissions {arquivo.csv} --source=form --event=1
```

Lê o CSV do Forms, casa por e-mail do líder, cria a submissão com
`source`, `recorded_by` e `original_submitted_at`. Conflito com submissão
existente → relatório pra decisão humana, nunca sobrescrita automática.

Tela `/admin/submissoes/lancar` faz o mesmo caso a caso, pros degraus 3 e 4.

**Toda submissão com `source != web` aparece marcada no painel do organizador**
até ser conferida. Transparência aqui evita acusação de favorecimento depois.

## A.5 Backup durante o evento

- `pg_dump` a cada 15 min pra disco local + cópia em nuvem, durante todo o evento
- Snapshot manual nomeado **imediatamente antes e depois do deadline**
- `rsync` dos uploads junto — banco sem os arquivos não restaura nada
- Testar a **restauração** na semana 8. Backup não testado é fé, não backup

## A.6 Jurados sem sistema

Rubrica impressa, uma folha por projeto avaliado, com nome e assinatura do
jurado. Digitação posterior com `source` equivalente. As folhas ficam arquivadas
como comprovante até o fim do prazo de recurso.

## A.7 Kit físico de contingência

- Roteador 4G ou celular com hotspot configurado e testado (não descobrir a
  senha do hotspot às 23h50)
- Notebook do organizador com o projeto rodando local e o banco restaurado
- Pendrive pra receber arquivo de equipe sem rede
- Prancheta, folhas de submissão e rubricas impressas
- Cartão A4 com o runbook, colado na mesa da organização

## A.8 Runbook — uma página, impressa

| Sintoma | Ação imediata | Quem |
|---|---|---|
| Site não abre pra ninguém | Declarar incidente → divulgar Degrau 2 → avisar no grupo/microfone | Coord. técnico |
| Site abre, upload falha | Orientar Degrau 1 (só o link) | Monitor |
| 1 equipe sem acesso | Pendrive ou hotspot; se falhar, Degrau 4 | Monitor |
| Rede caiu perto do deadline | Declarar incidente **com extensão** e anunciar em voz alta | Coordenação |
| Banco corrompido | Parar o app, restaurar último dump, reprocessar imports | Coord. técnico |
| Jurado sem acesso | Entregar rubrica impressa | Coordenação |

Regra que evita 90% do caos: **uma pessoa** declara incidente e anuncia. Se três
pessoas dão instruções diferentes ao mesmo tempo, o problema técnico vira
problema de confiança.

## A.9 O que testar na semana 8

- [ ] Derrubar o app com 5 equipes submetendo — todas caem no Degrau 2 sem ajuda
- [ ] Importar CSV do Forms com 1 conflito proposital
- [ ] Restaurar backup completo (banco + arquivos) do zero, cronometrado
- [ ] Declarar incidente com extensão e conferir que valeu pra todos
- [ ] Lançar submissão manual e confirmar a marcação no painel
- [ ] Ler o runbook em voz alta com a equipe de organização
