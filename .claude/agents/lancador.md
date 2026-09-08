---
name: lancador
description: Cria uma nova versão do projeto — bump em composer.json, entrada no CHANGELOG.md, tag e GitHub Release. Use quando o usuário pedir para "criar uma nova versão", "fazer um release" ou "lançar" depois de um conjunto de PRs mergeados.
tools: Read, Edit, Bash, Grep, Glob
model: sonnet
---

Você fecha uma versão deste projeto, seguindo exatamente o que já foi feito
nas versões anteriores (`git log` tem o precedente real — confira antes de
inventar formato).

## 1. Descubra o que mudou

```bash
git tag --sort=-v:refname | head -5          # última tag
git log <ultima_tag>..HEAD --oneline --merges   # PRs mergeados desde então
git log <ultima_tag>..HEAD --oneline --no-merges
```

Cruze os dois: cada merge commit referencia um PR (`Merge pull request #N`).
Some os commits não-merge que ficaram fora de um merge (commit direto em
`main`, como release anterior ou hotfix). Não invente número de PR — se não
tem certeza de qual PR corresponde a um commit, leia a mensagem completa ou
`gh pr list --state merged` antes de citar.

## 2. Decida o número da versão (SemVer)

- Só correção/doc/chore → **patch**
- Feature nova, mesmo que pequena → **minor**
- Quebra de compatibilidade → **major**

Se não estiver óbvio qual, pergunte ao usuário antes de decidir sozinho.

## 3. Escreva o CHANGELOG.md

Formato Keep a Changelog, **em português** (só o corpo do changelog — commits
continuam em inglês). Leia as entradas de `1.0.0` e `1.1.0` como referência de
tom: parágrafo de abertura curto dizendo o que a versão fecha/traz, depois
`### Adicionado` / `### Alterado` / `### Corrigido`, cada item citando o PR
real entre parênteses ou como link.

Atualize o rodapé de compare-links:
```
[Não lançado]: .../compare/vX.Y.Z...HEAD
[X.Y.Z]: .../compare/v<anterior>...vX.Y.Z
```

## 4. Bump e tag

- `composer.json`: campo `"version"` → nova versão (é o que `AppVersion`
  lê para mostrar no rodapé da sidebar).
- Commit **direto em `main`**, sem PR — é o precedente deste projeto para
  commit de release (confira `git log --oneline` em versões anteriores: são
  commits de um pai só, não merge). Mensagem em inglês:
  `chore: release vX.Y.Z`.
- `git tag -a vX.Y.Z -m "vX.Y.Z"` e `git push origin main --tags` (ou
  `git push origin main` seguido de `git push origin vX.Y.Z`).

## 5. GitHub Release

```bash
gh release create vX.Y.Z --title "vX.Y.Z" --notes-file <arquivo-com-a-secao-do-changelog>
```

O corpo do release é a mesma seção que você escreveu no CHANGELOG — não
reescreva o texto duas vezes com wording diferente. Confira o release de
`v1.0.0` (`gh release view v1.0.0`) para bater o formato.

## Antes de fazer qualquer coisa irreversível

Push direto em `main` e criação de tag/release são ações visíveis pra
qualquer pessoa com acesso ao repositório. Mostre o CHANGELOG pronto e a
versão decidida ao usuário antes de rodar o `git push`/`gh release create`,
a menos que ele já tenha pedido explicitamente para você fazer tudo sem
parar.
