# Sistema de Apoio ao 1º Hackathon — IFPR Pinhais

[![CI](https://github.com/joaopedroplinta/hackathon-ifpr/actions/workflows/ci.yml/badge.svg)](https://github.com/joaopedroplinta/hackathon-ifpr/actions/workflows/ci.yml)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Inertia](https://img.shields.io/badge/Inertia-v2-9553E9)
![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)
![TypeScript](https://img.shields.io/badge/TypeScript-strict-3178C6?logo=typescript&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-17-4169E1?logo=postgresql&logoColor=white)

Sistema web único que cobre o ciclo completo do evento: inscrição e formação
de equipes, agenda, check-in, submissão de projetos, avaliação por jurados e
divulgação de resultados.

Projeto acadêmico do curso de Tecnologia em Análise e Desenvolvimento de
Sistemas — **IFPR Campus Pinhais**, desenvolvido para apoiar a organização do
1º Hackathon do curso.

> **Status:** Semanas 0 a 8 concluídas — auth, equipes, submissões, agenda,
> check-in, avaliação por jurados, resultados, voto popular, certificados,
> regulamento, identidade visual e o ensaio geral (30 equipes, carga, plano B)
> já funcionam de ponta a ponta. Falta decidir a hospedagem (dado precisa
> ficar no Brasil, LGPD) e o polimento visual final — backlog completo em
> [`docs/`](docs) e no
> [GitHub Project](https://github.com/users/joaopedroplinta/projects/9).

---

## Sumário

- [Equipe](#equipe)
- [Visão geral](#visão-geral)
- [Papéis do sistema](#papéis-do-sistema)
- [Stack técnica](#stack-técnica)
- [Arquitetura](#arquitetura)
- [Como rodar localmente](#como-rodar-localmente)
- [Testes e qualidade](#testes-e-qualidade)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Documentação](#documentação)
- [Cronograma](#cronograma)
- [Plano B — dia do evento](#plano-b--dia-do-evento)
- [Licença](#licença)

---

## Equipe

| Nome |
|---|
| João Pedro dos Santos Henrique Plinta |
| Jair Rosa de Aguiar Neto |
| João Pedro Camargo dos Santos |

---

## Visão geral

O sistema acompanha o hackathon nas três fases do evento:

- **Antes** — inscrição, formação de equipes, agenda pública
- **Durante** — check-in, submissão de projetos, acompanhamento da agenda
- **Depois** — avaliação pelos jurados, resultados públicos, certificados

Requisito transversal: o sistema precisa sobreviver ao dia do evento. Rede
instável, dezenas de equipes submetendo no último minuto e jurado avaliando
pelo celular são o cenário normal, não a exceção — por isso existe um
[plano B](#plano-b--dia-do-evento) inteiro dedicado a degradação controlada.

### Funcionalidades

- Inscrição com login por e-mail/senha ou Google (restrito a domínio
  institucional)
- Formação de equipes por convite (e-mail ou código), com trilhas/categorias
- Submissão de projetos com upload, versionamento e prazo validado no servidor
- Avaliação por jurados com rubrica configurável, autosave e distribuição
  automática respeitando conflito de interesse
- Cálculo de resultado materializado, publicação manual e explícita,
  pódio geral e por trilha
- Voto popular com uma vitrine pública de projetos
- Check-in por QR code com fallback de busca manual
- Certificados em PDF com página pública de validação
- Regulamento público com critério de desempate, regra de contingência e
  upload do edital oficial em PDF
- Auditoria (`activity log`) de toda alteração sensível: nota, desqualificação,
  publicação de resultado

## Papéis do sistema

| Papel | Pode |
|---|---|
| **guest** | Ver agenda, resultados publicados e páginas públicas de projeto |
| **participante** | Criar/entrar em equipe, submeter projeto, votar no prêmio popular, baixar certificado |
| **jurado** | Ver só as submissões atribuídas a ele, avaliar, comentar |
| **organizador** | CRUD de evento/agenda/equipes, check-in, atribuir jurados, publicar resultados |
| **admin** | Tudo, além de gerenciar usuários e papéis |

Papéis acumulam (um monitor pode ser participante e organizador) e toda regra
de acesso vive em **Policies** — nunca em `if` espalhado no controller. Ver
[`.claude/rules/security.md`](.claude/rules/security.md).

## Stack técnica

| Camada | Escolha | Motivo |
|---|---|---|
| Backend | Laravel 12 | Ecossistema completo: auth, filas, notificações, storage |
| Ponte | Inertia v2 | React sem escrever API REST, sem CORS, sem gerenciar token |
| Frontend | React 19 + TypeScript | Tipagem nas props vindas do controller |
| Estilo | Tailwind v4 + shadcn/ui | Starter kit oficial já entrega isso configurado |
| Banco | PostgreSQL 17 (Docker) | Mesmo banco em dev e prod — sem surpresa de tipo no deploy |
| Filas | Driver `database` | E-mails e PDFs fora do request |
| Testes | Pest 3 | Sintaxe enxuta, feature tests cobrem o fluxo real |

**Pacotes de domínio:**

```
laravel/socialite                 # Google OAuth
spatie/laravel-permission         # papéis e permissões
barryvdh/laravel-dompdf           # certificados em PDF (PHP puro, sem Chromium)
bacon/bacon-qr-code               # QR em SVG, sem dependência de imagick
spatie/laravel-activitylog        # auditoria de notas e mudanças sensíveis
```

Decisões de stack, modelo de dados completo e telas planejadas estão em
[`PLANO.md`](PLANO.md).

## Arquitetura

Repositório único, um deploy — **sem API REST**. As páginas React recebem
dados por props do controller via `Inertia::render()`, não por `fetch`.

```
Controller (Http/Controllers/{Público}/…)
   │  autoriza via Policy, valida via Form Request
   ▼
Action (Actions/…)          ← regra de negócio com mais de uma tabela
   │
   ▼
Model (Eloquent)  ──────────► Inertia::render('pagina', [...])
                                     │
                                     ▼
                          Página React tipada (pages/…)
```

- **Controllers agrupados por público** (`Public`, `Participant`, `Judge`,
  `Organizer`), não por recurso — a pasta espelha a fronteira das Policies.
- **Actions** só para escrita que toca mais de uma tabela ou que precisa
  rodar de controller *e* de comando artisan. Leitura simples fica no
  controller.
- **Sem `Services/`** — vira classe-deus sem relação entre métodos.

Detalhes completos em [`.claude/rules/estrutura.md`](.claude/rules/estrutura.md).

## Como rodar localmente

Pré-requisitos: PHP 8.2+, Composer, Node 20+, Docker.

```bash
git clone https://github.com/joaopedroplinta/hackathon-ifpr.git
cd hackathon-ifpr

docker compose up -d      # Postgres 17 + Mailpit
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate

npm run dev                # terminal 1 — Vite
php artisan serve          # terminal 2 — app em http://localhost:8000
```

Filas (e-mails e PDFs) rodam fora do request:

```bash
php artisan queue:work
```

Login com Google é opcional em dev — guia de configuração (~10 min) em
[`docs/`](docs).

### Comandos de domínio

```bash
php artisan hackathon:compute-results {event}     # recalcula ranking
php artisan hackathon:import-submissions {csv}    # plano B: importa do Google Forms
```

## Testes e qualidade

```bash
./vendor/bin/pest              # testes (Feature + Unit)
./vendor/bin/pint               # formatação PHP
npm run lint:check              # ESLint (não usar `lint`, que corrige em silêncio)
npm run format:check            # Prettier
npx tsc --noEmit                # tipos
```

O CI ([`ci.yml`](.github/workflows/ci.yml)) roda os cinco a cada push e pull
request, contra Postgres 17. Rode localmente antes de abrir PR — é mais
rápido que esperar o CI reprovar.

**Definição de pronto** de uma feature (checada com o comando `/pronto`):
Policy cobrindo acesso, validação em Form Request, teste Pest do caminho feliz
e de pelo menos 1 erro, layout de celular, estados de vazio/carregando/erro
na UI.

## Estrutura do projeto

```
app/
├── Enums/            status, tipos e papéis — todo enum tem label() em português
├── Models/            Eloquent
├── Policies/          uma por model com regra de acesso
├── Actions/           escrita com regra de negócio, agrupada por domínio
├── Http/
│   ├── Controllers/   agrupado por público: Public, Participant, Judge, Organizer, Auth
│   └── Requests/      mesma divisão dos controllers
├── Support/           helpers de domínio sem estado
└── Console/Commands/  hackathon:compute-results, hackathon:import-submissions

resources/js/
├── pages/             espelha o backend: publico, equipe, submissao, jurado, admin
├── components/
│   ├── ui/             shadcn — gerado por CLI, não editado à mão
│   └── hackathon/       componentes do domínio
├── layouts/
├── hooks/
└── types/             props tipadas por página, sem any

tests/
├── Feature/           espelha a estrutura dos controllers
└── Unit/               enums, cálculo, Support
```

## Documentação

| Arquivo | Conteúdo |
|---|---|
| [`PLANO.md`](PLANO.md) | Escopo, papéis, modelo de dados, telas, cronograma e decisões travadas |
| [`PLANO.md` § Anexo A](PLANO.md#anexo-a--plano-b) | Plano B para o dia do evento |
| [`CLAUDE.md`](CLAUDE.md) | Convenções de código e ferramentas do projeto |
| [`.claude/rules/`](.claude/rules) | Invariantes de estrutura, banco, segurança e frontend |
| [`docs/`](docs) | Guias de configuração (Google OAuth), canvas de descoberta e backlog do projeto |

## Cronograma

Sprint de 1 semana, 8 semanas ao todo. Detalhes de cada entrega em
[`PLANO.md § 6`](PLANO.md#6-cronograma--8-semanas).

| Semana | Entrega |
|---|---|
| 0 ✅ | Setup: Laravel + Inertia + React, Docker, CI, papéis, layout base |
| 1 ✅ | Autenticação: Google + e-mail/senha, perfil, inscrição no evento |
| 2 ✅ | Equipes: criar, convidar, entrar, travas de tamanho e prazo |
| 3 ✅ | Submissões: formulário, upload, versionamento, deadline no servidor |
| 4 ✅ | Agenda pública/admin + `.ics` e check-in por QR com fallback manual |
| 5 ✅ | Rubrica configurável, atribuição de jurados, painel do jurado com autosave |
| 6 ✅ | Cálculo de resultados, publicação controlada, voto popular |
| 7 ✅ | Certificados em PDF, notificações por e-mail em fila |
| 8 ✅ | Ensaio geral: carga, acessibilidade, responsivo, plano B — deploy segue pendente (decisão de hospedagem) |

## Plano B — dia do evento

Premissa central: **no deadline, o que importa é carimbar o tempo e guardar o
link do código** — deck, vídeo e descrição podem ser completados depois. Uma
escada de degradação define o que fazer se rede, upload ou o próprio sistema
falharem, do reenvio mínimo até papel e caneta. Runbook completo, backup e
kit físico de contingência em [`PLANO.md § Anexo A`](PLANO.md#anexo-a--plano-b).

## Licença

Projeto acadêmico — IFPR Campus Pinhais. Sem licença de distribuição definida.
