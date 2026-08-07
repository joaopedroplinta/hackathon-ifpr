# CLAUDE.md

Sistema de apoio ao 1º Hackathon do curso de tecnologia — IFPR Campus Pinhais.

## Estado atual

**Semana 0 concluída, estrutura de pastas definida.** Aplicação Laravel 12 + Inertia 2 + React 19 no ar,
Postgres via Docker, Pest verde, CI passando. Ainda não existe nenhuma tabela
de domínio do hackathon — a próxima etapa é a semana 1 (autenticação).

## Leia o PLANO.md primeiro

`PLANO.md` é a fonte de verdade sobre escopo, modelo de dados, cronograma e
decisões já tomadas. **Antes de propor arquitetura, tabela nova ou mudança de
stack, verifique se a decisão já está lá.** O Anexo A descreve o plano B do dia
do evento — mexer em deadline, submissão ou importação exige ler aquela seção.

## Stack

Laravel + Inertia v2 + React + TypeScript + Tailwind v4, PostgreSQL, Pest.
Repositório único, um deploy. **Não escrever API REST** — as páginas recebem
props via `Inertia::render()`.

## Comandos

```bash
docker compose up -d              # Postgres + Mailpit
php artisan migrate               # migrations
npm run dev                       # Vite (front)
php artisan serve                 # app em :8000
php artisan queue:work            # filas: e-mails e PDFs
./vendor/bin/pest                 # testes
./vendor/bin/pint                 # formatação PHP
npm run lint                      # ESLint + Prettier

php artisan hackathon:compute-results {event}     # recalcula ranking
php artisan hackathon:import-submissions {csv}    # plano B: importa Forms
```

O CI (`.github/workflows/ci.yml`) roda Pint, ESLint, `tsc --noEmit` e Pest
contra Postgres 17 a cada push e PR. Antes de abrir PR, rode os quatro
localmente — é mais rápido que esperar o CI reprovar.

`migrate:fresh`, `migrate:rollback` e `db:wipe` estão negados em
`.claude/settings.json`. Se precisar recriar o banco, peça ao usuário —
perder dados de teste no meio de um sprint custa mais que o tempo economizado.

## Convenções

**Idioma:** interface, rotas e mensagens de erro em **português**. Código,
nomes de tabela, migrations, comentários e commits em **inglês**.

**Autorização:** toda regra de acesso em Policy. Nunca `if ($user->role === ...)`
espalhado em controller.

**Validação:** sempre em Form Request. Validação só no front não conta.

**Datas:** UTC no banco, `America/Sao_Paulo` na exibição. Prazo é sempre
comparado com `now()` no servidor — nunca confiar em horário vindo do cliente.

**Dinheiro/nota:** nota é `decimal`, não `float`.

**Estrutura de pastas:** ver `.claude/rules/estrutura.md`. Em resumo:
controllers agrupados por público (`Public`, `Participant`, `Judge`,
`Organizer`), escrita com regra de negócio em `app/Actions/`, e
`resources/js/pages/` espelhando essa mesma divisão. Arquivos do front em
minúsculo com hífen.

**Formulário tipado:** use `type`, não `interface`, para o tipo do `useForm`.
O Inertia v2 exige `T extends FormDataType` e só o `type` ganha index
signature implícita — com `interface` o `tsc` reprova.

**Migrations:** uma tabela por migration, com `down()` funcionando de verdade.

## Regras

Invariantes do projeto, carregados junto com este arquivo:

@.claude/rules/estrutura.md
@.claude/rules/database.md
@.claude/rules/security.md
@.claude/rules/frontend.md

## Ferramentas do projeto

**Skills** (carregam sozinhas quando o assunto aparece)
- `slice-inertia` — construir feature vertical completa, com exemplos de código
- `regras-avaliacao` — cálculo de nota, desempate, jurados, resultado, voto

**Comandos** (você dispara)
- `/sprint [n]` — abre o sprint da semana N: situação real vs. PLANO.md e plano
- `/pronto` — confere as mudanças atuais contra a Definição de Pronto
- `/ensaio` — simula o dia do evento e valida o plano B

**Agentes**
- `revisor-regras` — caça violação de prazo, autorização, upload e nota
- `testador` — escreve testes Pest de uma feature já pronta

## Testar no navegador

Complementa os testes, não substitui: o CI não roda navegador, então nada
verificado só ali protege contra regressão. Serve para o que o Pest não vê —
texto na tela, layout, erro de console.

```bash
docker compose up -d && php artisan serve   # :8000
npm run dev                                  # outro terminal
```

Três armadilhas, todas já cobradas caro aqui:

- **Não use `form_input` em tela React.** Ele altera o DOM sem o React saber,
  o estado do formulário fica vazio e o POST vai sem dados. Pior: digitar por
  cima mistura o valor antigo com o novo.
- **Clique por referência de elemento (`ref`), não por coordenada.** A janela
  muda de tamanho entre capturas e a coordenada fura. `read_page` com filtro
  `interactive` devolve os refs.
- **A validação nativa do Chrome barra o envio antes do servidor.** Para
  exercitar regra de servidor, os dados precisam passar pelo HTML5 e falhar
  só no PHP.

## Definição de pronto

Feature não está pronta sem: Policy, Form Request, teste Pest do caminho feliz
e de pelo menos 1 erro, layout de celular, estados de vazio/carregando/erro na
UI. Use `/pronto` para conferir antes de fechar um sprint.

## Cuidado

- `.env` não é lido nem editado por aqui — está negado nas permissões
- Uploads ficam em `storage/app/private/`, fora do webroot
- Não commitar `.env`, `storage/app/`, `node_modules/`, `vendor/`
- `.claude/settings.local.json` (se criado) vai no `.gitignore`
