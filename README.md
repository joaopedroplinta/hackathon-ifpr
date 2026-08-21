# Hackathon IFPR

[![CI](https://github.com/joaopedroplinta/hackathon-ifpr/actions/workflows/ci.yml/badge.svg)](https://github.com/joaopedroplinta/hackathon-ifpr/actions/workflows/ci.yml)
![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![React 19](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)
![TypeScript](https://img.shields.io/badge/TypeScript-strict-3178C6?logo=typescript&logoColor=white)
![PostgreSQL 17](https://img.shields.io/badge/PostgreSQL-17-4169E1?logo=postgresql&logoColor=white)

Plataforma para operar um hackathon de ponta a ponta: inscrições, equipes,
submissões, avaliação, resultados, certificados e comunicação do evento.
Ela foi desenhada para reduzir trabalho manual da organização e continuar
operável nos momentos mais críticos do evento.

Desenvolvida para o **1º Hackathon do IFPR Campus Pinhais**.

## O que a plataforma resolve

| Momento | Recursos |
| --- | --- |
| Antes do evento | inscrição, autenticação institucional, formação de equipes, agenda e regulamento |
| Durante o evento | check-in por QR Code, submissão com prazo validado no servidor e acompanhamento da programação |
| Avaliação | rubricas configuráveis, distribuição de jurados, conflitos de interesse e salvamento automático |
| Encerramento | ranking calculado, publicação controlada dos resultados, voto popular e certificados verificáveis |

Também estão incluídas uma trilha de auditoria para operações sensíveis, o
versionamento de submissões, notificações em fila e alternativas de
contingência para o dia do evento.

## Comece em poucos minutos

### Pré-requisitos

- PHP 8.2 ou superior
- [Composer](https://getcomposer.org/)
- Node.js 20 ou superior
- Docker e Docker Compose

### Instalação

```bash
git clone https://github.com/joaopedroplinta/hackathon-ifpr.git
cd hackathon-ifpr

docker compose up -d
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Inicie a aplicação, o worker de fila, os logs e o Vite em um único comando:

```bash
composer dev
```

Acesse [http://localhost:8000](http://localhost:8000). O Mailpit fica em
[http://localhost:8025](http://localhost:8025), para inspecionar os e-mails
enviados localmente.

> O seed padrão cria uma conta de organização: `organizacao@ifpr.edu.br` /
> `password`. Troque essa credencial em qualquer ambiente que não seja local.

### Dados para demonstração

Para preencher a aplicação com uma edição encerrada, resultados publicados e
uma edição atual com equipes inscritas, execute:

```bash
php artisan db:seed --class=DemoSeeder
```

O seeder é idempotente: pode ser executado novamente sem duplicar o cenário.

## Operação local

Os serviços de apoio são iniciados pelo Docker Compose:

| Serviço | Endereço | Uso |
| --- | --- | --- |
| PostgreSQL 17 | `localhost:5432` | banco principal e de testes |
| Redis 7 | `localhost:6379` | filas e cache |
| Mailpit | `localhost:8025` | visualização de e-mails em desenvolvimento |

Caso prefira processos separados, use `npm run dev` para o frontend,
`php artisan serve` para a aplicação e `php artisan queue:work` para as filas.

O login com Google é opcional no desenvolvimento. Veja o guia em
[docs/google-oauth.md](docs/google-oauth.md).

## Arquitetura

O projeto é um monólito Laravel com Inertia: o backend entrega páginas React
tipadas, sem uma API REST intermediária. Essa escolha simplifica autenticação,
autorização e deploy, mantendo uma única aplicação para operar.

```text
Browser
  │
  ▼
Laravel routes → Controller → Policy / Form Request → Action → Eloquent
  │                                                        │
  └──────── Inertia props ← React + TypeScript ←──────────┘
```

- **Policies** concentram as regras de acesso por papel.
- **Form Requests** validam entradas antes da regra de negócio.
- **Actions** encapsulam operações que coordenam mais de um modelo, como
  distribuição de jurados e cálculo de resultados.
- **Redis + queues** retiram e-mails e geração de PDFs do caminho da requisição.

## Stack

| Camada | Tecnologias |
| --- | --- |
| Aplicação | Laravel 12, PHP 8.2+ |
| Interface | Inertia v2, React 19, TypeScript, Tailwind CSS v4 e shadcn/ui |
| Dados e assíncrono | PostgreSQL 17, Redis e Laravel Queues |
| Integrações | Google OAuth, Resend, QR Code e geração de PDF |
| Qualidade | Pest, Laravel Pint, ESLint, Prettier e TypeScript |

## Qualidade

```bash
./vendor/bin/pest       # testes de unidade e integração
./vendor/bin/pint       # formatação PHP
npm run lint:check      # ESLint
npm run format:check    # Prettier
npx tsc --noEmit        # verificação de tipos
```

Essas verificações também são executadas no CI para cada push e pull request.

## Comandos úteis

```bash
php artisan hackathon:compute-results {event}
php artisan hackathon:import-submissions {csv}
php artisan queue:work
```

O comando de importação é parte do plano de contingência: permite recuperar
submissões registradas externamente caso seja necessário durante o evento.

## Estrutura do repositório

```text
app/
├── Actions/        regras de negócio que coordenam operações
├── Http/           controllers e validações por público do sistema
├── Models/         modelos Eloquent
├── Policies/       autorização
└── Jobs/            trabalho assíncrono, como certificados em PDF

resources/js/
├── pages/          telas React por área do produto
├── components/     componentes de interface e de domínio
├── layouts/        estruturas compartilhadas
└── types/          contratos TypeScript

tests/              testes de feature e unidade
docs/               documentos operacionais e de produto
deploy/             serviços de fila e agendador para produção
```

## Segurança e continuidade operacional

- Controle de acesso por papéis: participante, jurado, organizador e admin.
- Restrição de autenticação Google ao domínio institucional configurado.
- Auditoria de avaliações, desqualificações e publicação de resultados.
- Publicação explícita de resultados; o ranking não se torna público
  automaticamente.
- Check-in com QR Code e busca manual como alternativa.
- Procedimento de contingência para falhas de rede e de submissão. Consulte o
  [plano B](PLANO.md#anexo-a--plano-b).

Para produção, mantenha worker de fila e scheduler ativos. As unidades de
serviço e as instruções de instalação estão em [deploy/README.md](deploy/README.md).

## Documentação

| Documento | Descrição |
| --- | --- |
| [PLANO.md](PLANO.md) | escopo, decisões de produto, modelo de dados e plano de contingência |
| [docs/diagramas.md](docs/diagramas.md) | diagramas do sistema |
| [docs/backlog.md](docs/backlog.md) | itens em acompanhamento |
| [docs/google-oauth.md](docs/google-oauth.md) | configuração do login com Google |
| [docs/ropa.md](docs/ropa.md) | registro de operações de tratamento de dados |
| [CHANGELOG.md](CHANGELOG.md) | histórico de mudanças |

## Equipe

- João Pedro dos Santos Henrique Plinta
- Jair Rosa de Aguiar Neto
- João Pedro Camargo dos Santos

## Licença

Distribuído sob a [licença MIT](LICENSE).
