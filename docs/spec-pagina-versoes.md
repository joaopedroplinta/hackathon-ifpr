# Spec — página pública de versões (`/versoes`)

## Problema

Participantes, jurados e organização não têm onde ver o que mudou no sistema.
A versão aparece no rodapé público e na sidebar (`app_version`), mas não leva a
lugar nenhum. O `CHANGELOG.md` existe e é mantido a cada release (agente
`lancador`), porém só é visível no GitHub, num formato técnico.

## Objetivo

Página pública, sem login, que lista as versões lançadas, da mais nova para a
mais antiga, com o resumo e as mudanças que importam para quem usa o sistema.

## Fora de escopo

- Cadastro/edição de notas de versão pelo painel (nada de tabela nova).
- Aviso de "novidades" após login, badge de versão nova, RSS.
- Notas de versão separadas do CHANGELOG (duas fontes divergem).

## Decisões

**Fonte única: `CHANGELOG.md`.** Lido em tempo de execução, no servidor. Já vai
para a imagem de produção (`COPY . .`; o `.dockerignore` exclui `docs/`, não o
CHANGELOG). O repositório é público, então a página não expõe nada que já não
esteja exposto no GitHub.

**Filtro de público.** O CHANGELOG mistura mudanças de uso com bastidores. A
página mostra:

| Parte do CHANGELOG                          | Na página? |
| ------------------------------------------- | ---------- |
| `## [Não lançado]`                          | não        |
| Versão, data                                | sim        |
| 1º parágrafo do texto de abertura           | sim (resumo) |
| Demais parágrafos/listas antes do 1º `###` (notas de deploy) | não |
| `### Adicionado`, `### Alterado`, `### Corrigido`, `### Segurança` | sim |
| `### Infra e processo`, `### Testes`, `### Documentação` e qualquer outra | não |

A allowlist de seções fica numa constante no parser. Seção nova no CHANGELOG
some da página até alguém decidir incluí-la — falha para o lado seguro.

**Markdown → HTML no servidor**, com `Str::inlineMarkdown()` (league/commonmark,
já instalado) por item e por resumo, com `html_input => 'escape'` e
`allow_unsafe_links => false`. O front recebe HTML pronto e já sanitizado; não
entra lib de markdown no bundle.

**Sem banco, sem cache.** O arquivo tem ~560 linhas; parsear por request custa
menos que invalidar cache a cada deploy. Rever se a página aparecer no perfil.

## Back-end

**Rota** (`routes/web.php`, bloco das públicas, sem `auth`):

```php
// Notas de versão, lidas do CHANGELOG.md -- ver Support\Changelog.
Route::get('versoes', [ChangelogController::class, 'index'])->name('versoes.index');
```

**`app/Support/Changelog.php`** — helper sem estado, mesmo lugar de `AppVersion`.

- `parse(string $markdown): array` — recebe o texto, devolve a lista. Recebe
  string (não caminho) para o teste unitário não depender de arquivo.
- `fromFile(): array` — lê `base_path('CHANGELOG.md')`; arquivo ausente ou
  ilegível devolve `[]`, nunca exceção.
- Regras de parse:
  - versão: linha `## [X.Y.Z] - AAAA-MM-DD`; ignora `## [Não lançado]` e
    cabeçalho que não case com o padrão.
  - item de lista: começa em `- `; linhas seguintes indentadas são
    continuação do mesmo item (o CHANGELOG quebra linha em ~80 colunas).
  - sub-listas e blocos de código dentro de seção permitida: ignorados.
  - seção sem nenhum item após o filtro não aparece; versão sem resumo e sem
    seção aparece só com número e data.

Formato devolvido (props da página):

```php
[
    [
        'numero' => '1.3.5',
        'data' => '2026-11-13',          // string ISO, formatada no front
        'resumo' => '<html inline>' | null,
        'secoes' => [
            ['titulo' => 'Corrigido', 'itens' => ['<html inline>', ...]],
        ],
    ],
]
```

**`app/Http/Controllers/Public/ChangelogController.php`** — mesmo molde do
`CookiePolicyController`: sem Policy (página pública sem dado de evento), sem
Form Request (não há entrada).

```php
public function index(Changelog $changelog): Response
{
    return Inertia::render('publico/versoes', [
        'versoes' => $changelog->fromFile(),
        'atual' => (new AppVersion)->numero(),
    ]);
}
```

## Front-end

**`resources/js/pages/publico/versoes.tsx`** — `PublicLayout` com título
"Versões" e descrição curta ("O que mudou no sistema a cada atualização.").

- Uma `<section id="v1.3.5">` por versão, para link direto (`/versoes#v1.3.5`).
- Cabeçalho da seção: `v1.3.5`, data em `America/Sao_Paulo` por extenso
  ("13 de novembro de 2026"), selo "Versão atual" (texto, não só cor) na
  versão igual a `atual`.
- Resumo em parágrafo; cada seção como `<h3>` + `<ul>`. HTML vindo do servidor
  via `dangerouslySetInnerHTML` — seguro porque o servidor escapa HTML cru.
- Código inline (`<code>`) com estilo de `font-mono`, quebrando linha
  (`break-words`) para não rolar na horizontal no celular.
- **Vazio:** "Nenhuma versão publicada ainda." (arquivo ausente ou sem versões).
- Carregando/erro: não se aplica — props chegam no render do Inertia, e erro de
  leitura vira lista vazia no servidor.

**Tipos** em `resources/js/types/` (`Versao`, `SecaoVersao`).

**Pontos de entrada:**

- `rodape-publico.tsx`: o `{app_version}` vira `<Link href={route('versoes.index')}>`
  e entra "Versões" na coluna "Comunidade".
- `app-sidebar.tsx`: o `{app_version}` do rodapé da sidebar vira link para a
  mesma rota (mesmo estilo, com foco visível).

## Testes

**Unit — `tests/Unit/Support/ChangelogTest.php`** (texto fixo, sem arquivo):

- ordem preservada; `[Não lançado]` ignorado.
- resumo = só 1º parágrafo; notas de deploy fora.
- `Infra e processo`/`Testes`/`Documentação` fora; `Corrigido` dentro.
- item com continuação em várias linhas vira um item só.
- `<script>` no texto sai escapado; link `javascript:` não vira `href`.

**Feature — `tests/Feature/Public/ChangelogControllerTest.php`:**

- visitante sem login recebe 200 e componente `publico/versoes`.
- prop `versoes` traz a versão do `composer.json` como primeira (amarra
  CHANGELOG e `version` — release sem entrada no CHANGELOG quebra o CI).

**Vitest — `publico.test.tsx`** (ao lado das outras públicas):

- renderiza versão, data em português e selo "Versão atual".
- lista vazia mostra a mensagem de vazio.

## Definição de pronto

- [ ] Rota, `Support\Changelog`, controller, página, tipos
- [ ] Links no rodapé público e na sidebar
- [ ] Testes Unit, Feature e Vitest passando; Pint, ESLint, Prettier, `tsc`
- [ ] Conferido no celular (375 px) sem rolagem horizontal
- [ ] Validado em homologação antes da `main`
- [ ] Entrada em `## [Não lançado]` → `### Adicionado` no CHANGELOG

## Em aberto

- Link `(#224)` para a issue no GitHub: regex simples, mas fica para depois —
  para quem não é dev, número de issue não diz nada.
