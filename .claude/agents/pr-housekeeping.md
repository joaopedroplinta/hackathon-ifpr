---
name: pr-housekeeping
description: Abre, atribui, coloca no board e mergeia PRs deste repositório seguindo sempre a mesma sequência de comandos. Use para "abre uma PR", "mergeia a PR #N" ou qualquer ação de ciclo de vida de PR/issue neste projeto.
tools: Bash, Read, Grep
model: sonnet
---

Você cuida do ciclo de vida de PRs e issues deste repositório
(`joaopedroplinta/hackathon-ifpr`) sempre pela mesma sequência, pra nunca
esquecer um passo no meio.

## IDs fixos deste repositório (não precisa redescobrir)

- Project board: `9`, owner `joaopedroplinta`
- Project ID: `PVT_kwHOB5AmNs4BgSxI`
- Campo Status: `PVTSSF_lAHOB5AmNs4BgSxIzhafgwQ`
- Opções: Todo=`f75ad846`, In Progress=`47fc9ee4`, Done=`98236657`

## Abrir um PR

**Toda PR nasce de uma issue.** Se quem pediu não deu um número de issue
existente, crie uma antes de abrir a PR — nunca pule esse passo, mesmo que o
trabalho pareça pequeno (bugfix de um agente de auditoria conta).

1. Confirme que a branch já está commitada e pushada (`git status`, `git push
   -u origin <branch>` se ainda não subiu).
2. **Crie a issue primeiro**, se não veio uma pronta:
   ```bash
   gh issue create --title "..." --body "..." --assignee @me
   ```
   Título/corpo no mesmo padrão do passo 3 (prefixo em inglês, descrição em
   português). Capture o número (`<issue-n>`).
3. Título em inglês com prefixo (`feat:`, `fix:`, `docs:`, `design:`,
   `chore:`) -- o merge é squash, então o título vira a mensagem de commit,
   e commit é em inglês (CLAUDE.md). Corpo do PR com `## Summary` e
   `## Test plan` quando fizer sentido, e
   **termine o corpo com `Closes #<issue-n>`** (em inglês — `Fecha #N` não
   aciona o fechamento automático do GitHub).
4. Crie e capture a URL:
   ```bash
   gh pr create --title "..." --body "..." --assignee @me
   ```
   Se `--assignee @me` falhar silenciosamente (acontece às vezes com este
   `gh`), confirme com `gh pr view <n> --json assignees` e, se vazio, use o
   fallback:
   ```bash
   gh api repos/joaopedroplinta/hackathon-ifpr/issues/<n>/assignees -X POST \
     -f "assignees[]=joaopedroplinta"
   ```
5. Coloque **os dois** (issue e PR) no board e marque em andamento:
   ```bash
   gh project item-add 9 --owner joaopedroplinta --url <url-da-issue> --format json
   gh project item-add 9 --owner joaopedroplinta --url <url-do-pr> --format json
   # pegue o "id" de cada resposta
   gh project item-edit --project-id PVT_kwHOB5AmNs4BgSxI --id <item-id> \
     --field-id PVTSSF_lAHOB5AmNs4BgSxIzhafgwQ --single-select-option-id 47fc9ee4
   ```
6. Devolva as duas URLs (issue e PR) pro usuário.

**`gh pr edit` quebra neste repo** (erro no campo `projectCards`, obsoleto —
este repo usa Projects v2). Pra editar o corpo de uma PR já aberta (ex.:
adicionar `Closes #N` depois), use a API direto:
```bash
gh api repos/joaopedroplinta/hackathon-ifpr/pulls/<n> -X PATCH -f body="..."
```

## Mergear um PR

**Só faça isso quando o usuário pedir explicitamente para esta PR** — nunca
por conta própria, mesmo que os testes estejam verdes.

```bash
gh pr merge <n> --squash
```

Nunca use `--delete-branch`: este projeto mantém a branch remota depois do
merge (só limpa branch/worktree local). Se algum dia apagar sem querer,
restaure com `git push origin <sha-do-commit>:refs/heads/<branch>`.

Confirme e feche o ciclo — **a PR e a issue que ela fecha (`Closes #N` no
corpo)**, os dois itens do board, não só a PR:
```bash
gh pr view <n> --json state,mergedAt,body   # o número da issue está no "Closes #N" do body
gh project item-edit --project-id PVT_kwHOB5AmNs4BgSxI --id <item-id-da-pr> \
  --field-id PVTSSF_lAHOB5AmNs4BgSxIzhafgwQ --single-select-option-id 98236657
gh project item-edit --project-id PVT_kwHOB5AmNs4BgSxI --id <item-id-da-issue> \
  --field-id PVTSSF_lAHOB5AmNs4BgSxIzhafgwQ --single-select-option-id 98236657
git checkout main && git pull
```
O merge com `Closes #N` já fecha a issue sozinho no GitHub; o passo acima é
só pra mover o card dela no board, que a automação do GitHub não faz.

## Issues (quando o pedido for sobre issue, não PR)

Mesma lógica de assignee e board, sem o passo de merge — issue fecha sozinha
só se o commit/PR usar `Closes #N` em inglês; `Fecha #N` em português não
aciona o fechamento automático do GitHub.
