# Regras — banco de dados

Invariantes. Violar qualquer uma destas quebra o evento ou o resultado.

## Tipos

- **Nota é `decimal(5,2)`**, nunca `float`. Média ponderada com float gera
  `8.299999999999999` e diferença de arredondamento entre jurados.
- **Peso de critério é `decimal(5,2)`**, mesma razão.
- Datas sempre `timestamp` (com timezone), nunca `datetime` string.
- Enum de status é `string` + cast para PHP enum. Enum nativo do Postgres
  exige migration pra adicionar valor — não vale a pena.

## Tempo

- Tudo em **UTC no banco**. `America/Sao_Paulo` só na exibição.
- Comparação de prazo usa `now()` do servidor. Data vinda do request nunca
  decide se algo está dentro do prazo.

## Escopo

- **Toda tabela de domínio tem `event_id`**, direto ou via relação clara.
  Query sem escopo de evento vaza dados da edição anterior.
- Unique composto com `event_id`: nome de equipe é único **por evento**,
  não globalmente.

## Integridade

- Foreign key com `onDelete` explícito. Pensar antes: apagar equipe apaga
  submissão (`cascade`), mas apagar usuário **não** apaga avaliação
  (`restrict` — a nota é registro histórico).
- `soft deletes` em `teams` e `submissions`. Organizador que apaga por engano
  às 23h precisa de desfazer.
- Índice em toda coluna usada em `where` de listagem: `event_id`, `status`,
  `team_id`, `judge_id`.

## Migrations

- Uma tabela por migration, `down()` funcionando de verdade.
- Nunca editar migration já aplicada em produção — criar uma nova.
- Alteração destrutiva (drop de coluna com dado) exige aviso explícito antes.
