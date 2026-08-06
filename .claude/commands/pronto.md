---
description: Confere as mudanças atuais contra a Definição de Pronto do PLANO.md
---

Revise o que mudou no working tree (`git status` + `git diff`) contra a
Definição de Pronto da seção 9 do `PLANO.md`.

Para cada arquivo/feature tocada, verifique concretamente — abrindo os arquivos,
não por suposição:

1. **Policy** — existe uma Policy cobrindo a ação? O controller chama
   `authorize()` ou o middleware `can:`? Rota pública é intencional?
2. **Form Request** — a entrada é validada no servidor? Regras batem com as
   constraints do banco (tamanho, unique, nullable)?
3. **Teste Pest** — existe teste do caminho feliz E de pelo menos um caminho de
   erro (sem permissão, dado inválido, prazo vencido)? Rode `./vendor/bin/pest`
   e reporte o resultado real.
4. **Celular** — as classes Tailwind têm breakpoint? Tabela larga tem scroll
   próprio? Formulário funciona em tela estreita?
5. **Estados da UI** — vazio, carregando e erro estão tratados, ou a tela quebra
   com lista vazia / requisição lenta?
6. **Português** — nenhuma string técnica em inglês vazando pro usuário
   (mensagem de exceção, nome de campo, enum cru).
7. **Prazo e fuso** — se a feature envolve deadline, a comparação usa `now()` no
   servidor? Datas exibidas em `America/Sao_Paulo`?

Ao final, liste em duas seções: **Bloqueia o merge** e **Pode ficar pra depois**.
Se estiver tudo certo, diga isso direto — sem inventar pendência pra parecer
útil.

$ARGUMENTS
