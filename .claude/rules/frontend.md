# Regras — React, Inertia e UI

## Inertia

- Página recebe dados por **props do controller**, não por `fetch`.
- Formulário usa `useForm` do Inertia — ele já traz `errors`, `processing`
  e `recentlySuccessful`. Não reimplementar estado de submit na mão.
- Erro de validação vem do Laravel em `errors`. Não duplicar a regra em Zod
  no front; validação do front é conveniência, a do servidor é a verdade.
- Dado usado em toda tela (usuário logado, evento atual, flash) vai em
  `HandleInertiaRequests::share()`, não repetido em cada controller.
- Link interno é `<Link>`, nunca `<a href>` — `<a>` recarrega a página inteira.

## Tipagem

- Props tipadas em `resources/js/types/`. Interface por página.
- Sem `any`. Se o tipo do backend é incerto, escreva o tipo real e ajuste
  o controller.

## Estados obrigatórios

Toda tela que lista ou carrega algo trata os três:

- **Vazio** — mensagem útil com a próxima ação ("Você ainda não tem equipe.
  Criar equipe"), nunca tabela em branco.
- **Carregando** — skeleton ou spinner. `processing` do `useForm` desabilita
  o botão e evita envio duplo.
- **Erro** — mensagem em português dizendo o que fazer, não o stack trace.

## Idioma

- Tudo que o usuário lê é **português**: rótulo, botão, mensagem de erro,
  estado vazio, título de página, e-mail.
- Enum nunca aparece cru na tela. `submitted` vira "Enviado".
- Código, variável, componente e comentário em inglês.

## Celular

- Metade do uso no dia do evento é no celular — jurado avaliando e
  organizador fazendo check-in.
- Mobile-first: escreva a classe base pro celular e use `md:`/`lg:` pra subir.
- Tabela larga vive dentro de `overflow-x-auto`. A página nunca rola na
  horizontal.
- Alvo de toque mínimo confortável em botão de ação (check-in, nota).

## Acessibilidade

- `<label>` associado a todo input.
- Erro de campo referenciado por `aria-describedby`.
- Nunca comunicar estado só por cor — aprovado/reprovado precisa de texto
  ou ícone junto.
- Foco visível. Não remover `outline` sem colocar substituto.
