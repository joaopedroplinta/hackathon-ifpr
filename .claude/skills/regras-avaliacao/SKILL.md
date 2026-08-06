---
name: regras-avaliacao
description: Regras de negócio de avaliação e resultado do hackathon — rubrica, pesos, cálculo da nota final, desempate, distribuição de jurados, conflito de interesse, voto popular e publicação do ranking. Use ao mexer em rubrica, critério, avaliação, atribuição de jurado, cálculo de nota, ranking, resultado ou voto popular.
---

# Avaliação e resultado

Esta é a parte do sistema que decide prêmio. Erro aqui não aparece em teste de
fumaça — aparece na frente da equipe que perdeu.

## Cálculo

Duas etapas, nesta ordem:

**1. Nota da avaliação** (um jurado, uma submissão) — média ponderada:

```
nota_avaliacao = Σ(score_i × peso_i) / Σ(peso_i)
```

**2. Nota final da submissão** — média simples entre os jurados:

```
nota_final = Σ(nota_avaliacao_j) / n_jurados_que_submeteram
```

Regras que não podem ser negociadas na implementação:

- Só entra avaliação com `status = submitted`. Rascunho nunca conta.
- O divisor é o número de jurados que **efetivamente submeteram**, não o número
  de atribuições. Jurado que faltou não zera a submissão.
- Se nenhum jurado submeteu, `final_score` é **nulo**, não zero. Zero é uma
  nota; nulo é ausência de nota, e o painel do organizador precisa distinguir.
- Tudo em `decimal`. Arredondamento só na exibição, nunca no cálculo
  intermediário.

## Desempate

Aplicado em ordem, definido no regulamento **antes** das inscrições:

1. Maior nota no critério de maior peso
2. Maior nota no segundo critério de maior peso
3. Submissão mais cedo (`submitted_at`)

Empate que sobrevive aos três é empate de verdade — o sistema mostra a mesma
colocação para ambos e o organizador decide.

## Normalização entre jurados

**Não implementar.** Decisão registrada no PLANO.md §7. Z-score é mais justo
estatisticamente e indefensável na conversa com a equipe que perdeu por 0,2.
Se aparecer pedido pra adicionar, avisar que é reversão de decisão e por quê.

O que existe no lugar: o painel do organizador mostra média e desvio por jurado.
Dispersão grande é sinal pra conversa, não pra correção automática.

## Distribuição de jurados

- N jurados por submissão, configurável no evento (padrão 3)
- Carga balanceada — ninguém com o dobro da fila do outro
- **Conflito de interesse bloqueia atribuição**, não só avisa. Orientador,
  parente ou colega de trabalho da equipe não recebe aquela submissão
- Distribuição é sugestão: o organizador ajusta na mão e o sistema não
  sobrescreve ajuste manual em uma redistribuição posterior
- Jurado ausente → reatribuição em 1 clique, e o cálculo aceita número variável
  de jurados por submissão

## Painel do jurado

- **Autosave de rascunho a cada mudança.** Jurado avalia pelo celular, em pé,
  com wi-fi de evento. Perder 20 minutos de nota digitada é falha grave.
- Rascunho não conta no resultado — só o envio explícito.
- Alterar nota já submetida exige justificativa e vai pro activity log.
- Progresso visível: "7 de 12 avaliadas".

## Resultado

- `results` é **materializado** por `php artisan hackathon:compute-results`.
  A página pública nunca agrega na hora — o ranking fica congelado e auditável.
- Recalcular é idempotente e registra `computed_at`.
- **Publicação é ação manual e explícita** do organizador
  (`events.results_published_at`). Calcular não publica.
- Antes de publicar, o painel mostra: submissões sem nota, jurados incompletos,
  empates pendentes. Publicar com pendência exige confirmação.

## Voto popular

- Prêmio **separado**. Nunca soma na nota técnica, nunca desempata a técnica.
- Unique `(event_id, user_id)` no banco — 1 voto por pessoa, garantido pelo
  banco e não pela aplicação.
- Só usuário autenticado e inscrito no evento.
- Contagem **escondida durante a votação**. Placar ao vivo cria efeito manada.
- Janela definida por `voting_opens_at` / `voting_closes_at`, checada no servidor.

## Ao alterar qualquer coisa aqui

Rode os testes de cálculo e confira contra um caso conferido na mão. Um teste
com 3 jurados, 4 critérios de pesos diferentes e um jurado ausente cobre a
maioria dos erros reais.
