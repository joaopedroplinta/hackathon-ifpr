---
description: Abre o sprint da semana N do PLANO.md — situação atual, o que falta, plano de execução
argument-hint: [número da semana]
---

Sprint alvo: **$1** (se vazio, descubra qual é o próximo sprint não concluído).

## 1. Situação real

Não confie no cronograma — confira o que existe de fato:

- `git log --oneline -15` e `git status`
- Migrations aplicadas: `php artisan migrate:status`
- Rotas existentes: `php artisan route:list --except-vendor`
- Testes: `./vendor/bin/pest --compact`

## 2. Compare com o PLANO.md

Leia a linha do sprint na tabela da seção 6 e o "pronto quando" dele.
Diga com clareza:

- O que já está feito
- O que falta pro critério de "pronto quando" ser atingido
- O que ficou pendente de sprints anteriores e vai atrapalhar este

## 3. Plano

Quebre o sprint em slices verticais entregáveis, na ordem de dependência.
Cada slice segue a skill `slice-inertia`: migration → policy → request →
controller → rota → tipo → página → teste.

Registre os slices como tasks. Um slice por task, com o critério de aceite
junto — não uma task genérica "fazer equipes".

## 4. Antes de começar

Levante o que pode travar e resolva agora:

- Decisão de produto que só o usuário pode tomar (pergunte, não assuma)
- Dependência externa (credencial do Google, domínio, servidor)
- Regra do PLANO.md que este sprint contradiz

Se nada travar, comece pelo primeiro slice.
