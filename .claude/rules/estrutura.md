# Regras — onde cada coisa mora

Arquivo no lugar errado é dívida silenciosa: ninguém acha, todo mundo duplica.

## Backend

```
app/
├── Enums/          status, tipos e papéis. Todo enum tem label() em português
├── Models/         plano, sem subpasta — Eloquent espera assim
├── Policies/       um por model que tem regra de acesso
├── Actions/        escrita com regra de negócio, agrupada por domínio
├── Http/
│   ├── Controllers/  agrupado pelo público: Public, Participant, Judge,
│   │                 Organizer, Auth
│   └── Requests/     mesma divisão dos controllers
├── Support/        helpers de domínio sem estado (cálculo, formatação)
└── Console/Commands/
```

**Controllers agrupados por público, não por recurso.** A pasta espelha a
fronteira das Policies: um controller em `Judge/` nunca toca dado de equipe
que não foi atribuída àquele jurado. Se você precisa do mesmo recurso em dois
públicos, são dois controllers com regras diferentes — não um com `if`.

**Action é classe de propósito único**, um método público `handle()`.
`CreateTeam`, `SubmitProject`, `ComputeResults`. Serve para:

- escrita que toca mais de uma tabela
- regra que precisa rodar de controller **e** de comando artisan
- lógica que merece teste isolado, sem passar por HTTP

Leitura simples e listagem ficam no controller — Action para `index()` é
cerimônia sem ganho.

**Nada de `Services/`.** Vira classe-deus com 15 métodos sem relação.

## Frontend

```
resources/js/
├── pages/          espelha o backend: publico, equipe, submissao,
│                   jurado, admin (+ auth e settings do starter kit)
├── components/
│   ├── ui/         shadcn — gerado por CLI, não editar à mão
│   └── hackathon/  componentes do domínio
├── layouts/
├── hooks/
└── types/
```

Tudo em **minúsculo com hífen**: `cartao-equipe.tsx`, não `CartaoEquipe.tsx`.
O componente exportado continua em PascalCase.

**Não editar `components/ui/`.** É saída do shadcn e será sobrescrita. Precisa
de variação? Componha em `components/hackathon/`.

## Enums

Todo enum tem `label(): string` em português. Enum nunca chega cru na tela.
`EnumLabelsTest` quebra o CI se um case novo ficar sem label — `match()` sem
o case correspondente estoura em runtime, ou seja, na cara do usuário.

Métodos de regra moram no enum quando dependem só dele:
`SubmissionStatus::countsForEvaluation()`, `Role::isStaff()`.

## Testes

`tests/Feature/` espelha a estrutura dos controllers.
`tests/Unit/` para o que não precisa de HTTP nem banco: enums, cálculo, Support.
