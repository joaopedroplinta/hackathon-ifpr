## O que muda

<!-- Uma frase. Se precisar de três, provavelmente são dois PRs. -->

## Como testar

<!-- Passos pra quem revisa reproduzir. Usuário/papel necessário. -->

## Definição de pronto (PLANO.md §9)

- [ ] Policy cobrindo quem pode fazer o quê
- [ ] Validação em Form Request (não só no front)
- [ ] Teste Pest: caminho feliz + pelo menos 1 erro
- [ ] Funciona em tela de celular
- [ ] Estados de vazio, carregando e erro tratados
- [ ] Textos em português, sem string técnica vazando

## Se mexeu em prazo, nota ou autorização

- [ ] Deadline comparado com `now()` no servidor
- [ ] Nota em `decimal`, nunca `float`
- [ ] Query escopada por `event_id`
- [ ] Rodei o agente `revisor-regras`
