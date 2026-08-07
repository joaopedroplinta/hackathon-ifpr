# Sistema de Apoio ao 1º Hackathon — IFPR Pinhais

Sistema web que cobre o ciclo completo do evento: inscrição e formação de
equipes, agenda, check-in, submissão de projetos, avaliação por jurados e
divulgação de resultados.

> **Status:** planejamento. A aplicação Laravel ainda não foi criada —
> por enquanto o repositório contém o plano, a configuração e o CI.

## Stack

Laravel · Inertia v2 · React + TypeScript · Tailwind v4 · PostgreSQL · Pest

Repositório único, um deploy. Sem API REST — as páginas React recebem props
direto do controller via Inertia.

## Documentação

| Arquivo | Conteúdo |
|---|---|
| [`PLANO.md`](PLANO.md) | Escopo, modelo de dados, telas, cronograma e decisões |
| [`PLANO.md` § Anexo A](PLANO.md) | Plano B para o dia do evento |
| [`CLAUDE.md`](CLAUDE.md) | Convenções de código e ferramentas do projeto |
| [`docs/google-oauth.md`](docs/google-oauth.md) | Configurar o login com Google (~10 min) |

## Começando

```bash
docker compose up -d      # Postgres + Mailpit
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev               # em outro terminal
php artisan serve
```

## Testes e qualidade

```bash
./vendor/bin/pest         # testes
./vendor/bin/pint         # formatação PHP
npm run lint              # ESLint
npx tsc --noEmit          # tipos
```

O CI roda os quatro a cada push e pull request.

## Licença

Projeto acadêmico — IFPR Campus Pinhais.
