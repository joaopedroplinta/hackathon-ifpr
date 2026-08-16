# RoPA — Registro das Operações de Tratamento de Dados Pessoais

Artefato de governança exigido pela LGPD (art. 37) além da política de
privacidade voltada ao participante (`/privacidade`). Aquela página explica
em linguagem simples o que é coletado e por quê; este documento é o
inventário técnico completo, mantido junto do código — issue #84.

**Atualizar sempre que uma migration adicionar, remover ou mudar o
propósito de uma coluna que guarda dado pessoal.** Este documento descreve
o sistema, não decide política — decisão de retenção ou base legal nova
passa pela organização, não só por quem mexe no schema.

## 1. Controlador e encarregado

- **Controlador:** Organização do 1º Hackathon IFPR Campus Pinhais.
- **Encarregado (DPO):** contato publicado em `/privacidade` —
  `hackathon@ifpr.edu.br`. Nomeação formal em documento institucional é a
  issue #83, ainda pendente; este RoPA já assume esse contato como ponto
  de entrada pra qualquer pedido do art. 18.
- **Hospedagem:** decisão travada em PLANO.md §7 — banco em qualquer
  ambiente é Postgres, sem SQLite. A região do servidor de produção é a
  issue #71 (ainda aberta), mas o requisito já registrado é **dado no
  Brasil** — não há transferência internacional prevista.

## 2. Categorias de titulares

| Categoria | Quem é |
|---|---|
| Participante | Qualquer pessoa inscrita no evento, com ou sem equipe |
| Líder de equipe | Participante com responsabilidades extra (convite, submissão) |
| Jurado | Avalia submissões atribuídas — pode ser externo à instituição |
| Organizador/admin | Equipe de organização do evento |
| Visitante | Não autenticado — só vê página pública, sem dado próprio coletado |

## 3. Operações de tratamento

Uma linha por tabela que guarda dado pessoal. Tabela sem dado pessoal
(`tracks`, `criteria`, `schedule_items`, `results`, `judge_assignments` em
si) fica fora — elas só referenciam pessoas por id de tabelas listadas
aqui.

| Tabela | Dado pessoal | Titular | Finalidade | Base legal (LGPD art. 7º) | Retenção | Compartilhado com |
|---|---|---|---|---|---|---|
| `users` | nome, e-mail, senha (hash), `google_id`, `avatar_url`, `qr_token` | Todos | Identidade, autenticação, crachá digital de check-in | Execução de procedimento preliminar / consentimento no cadastro (inc. V) | Enquanto a conta existir; anonimizado ao excluir (§5) | Ninguém fora do sistema. Senha nunca sai do hash |
| `event_registrations` | telefone, curso, tamanho de camiseta, **restrição alimentar** | Participante | Logística do evento (contato, credenciamento, alimentação) | Consentimento (inc. I) — restrição alimentar é opcional | Enquanto a inscrição existir; apagado (não anonimizado — zerado) ao excluir a conta | Organização, só o necessário pra operar o evento |
| `teams` / `team_members` | nome (via `leader_id`/`user_id`), papel na equipe | Participante | Formar e gerenciar equipes | Consentimento ao entrar na equipe | Enquanto a equipe existir (soft delete) | Outros membros da equipe, organização |
| `submissions` / `submission_files` | conteúdo do projeto enviado, autoria (`team_id`), nome original do arquivo (metadado, nunca usado no caminho de disco) | Participante | Avaliação do projeto, exibição pública pós-publicação | Execução do procedimento do evento (inc. V) | Registro do evento — não é apagado, é histórico da submissão | Jurados atribuídos; público em geral após `results_published_at`, só o que for exibido na vitrine |
| `evaluations` / `evaluation_scores` | nota e comentário do jurado sobre uma submissão | Jurado (autor) e Participante (sujeito da nota) | Cálculo de resultado | Execução do procedimento do evento (inc. V) | Permanente — nota já submetida é registro histórico (`.claude/rules/database.md`); alteração exige justificativa e vai pro `activity_log` | Organização; nota agregada aparece em `results`, comentário individual não é público |
| `judge_assignments` / `conflicts_of_interest` | vínculo jurado↔submissão, motivo de conflito de interesse declarado | Jurado | Distribuir avaliação, bloquear conflito de interesse | Execução do procedimento do evento (inc. V) | Permanente, histórico de quem avaliou o quê | Só organização |
| `attendances` / `checkpoints` | horário e método de check-in, quem confirmou (`checked_by`) | Participante | Registrar presença pra carga horária do certificado | Execução do procedimento do evento (inc. V) | Permanente — base do certificado emitido | Só organização; soma vira carga horária pública só pro próprio titular |
| `certificates` | nome, tipo, carga horária, `code` (uuid pra validação pública) | Participante/Jurado/Organizador | Emitir e validar certificado | Execução do procedimento do evento (inc. V) | Permanente | `/validar/{code}` expõe nome, tipo e carga horária a qualquer pessoa com o código — é o propósito do certificado público |
| `popular_votes` | quem votou em qual submissão | Participante | Apurar prêmio de voto popular | Consentimento ao votar | Permanente, mas contagem fica escondida durante a janela de votação (regras-avaliacao) | Resultado agregado é público; quem votou em quem não é exibido a ninguém |
| `incidents` | quem declarou o incidente (`declared_by`) | Organizador | Auditoria de decisão que afeta prazo de todo mundo | Legítimo interesse da organização (inc. IX) | Permanente | Público na página `/regulamento`/painel — é o propósito (transparência do plano B) |
| `activity_log` | autor, ação, valores antigo/novo de mudança sensível (nota, desqualificação, publicação) | Quem executou a ação | Auditoria e defesa em caso de contestação | Legítimo interesse / cumprimento de obrigação (inc. IX/II) | Permanente | Só organização, sob pedido em caso de contestação |
| `sessions` | ip, user agent, payload de sessão | Todos autenticados | Manter login | Execução do contrato (inc. V) | Enquanto a sessão viver (`SESSION_LIFETIME`) | Ninguém |

## 4. Dado sensível

Só um caso no sistema: **`event_registrations.dietary_notes`** (restrição
alimentar) — pode revelar condição de saúde ou convicção religiosa (art.
5º, II). Já é opcional (`nullable`), documentado em `/privacidade`, e
zerado (não só anonimizado) quando a conta é excluída (`AnonymizeUser`).
Nenhuma outra coluna do sistema se enquadra como dado sensível.

## 5. Direito de eliminação — como é operacionalizado

`App\Actions\Users\AnonymizeUser` é o único ponto de escrita pra exclusão
de conta (art. 18, VI):

- `users`: nome vira "Usuário removido", e-mail vira
  `removido-{id}@removido.local`, `google_id` e `avatar_url` zerados
- `event_registrations`: telefone, curso e restrição alimentar zerados
- Soft delete em `users` — escopo global do Eloquent já esconde de toda
  consulta normal, login para de funcionar
- **Não é apagado:** nota já submetida, certificado emitido, liderança de
  equipe histórica — ficam como registro (`restrictOnDelete` nas FKs de
  propósito), mas sem identificar a pessoa depois da anonimização

## 6. Segurança aplicada ao dado pessoal

Referência completa em `.claude/rules/security.md`; resumo do que é
relevante pra este inventário:

- Upload vai pra `storage/app/private` (disco `local`, fora do webroot),
  nome gerado pelo sistema, nunca o nome original enviado pela pessoa
- `qr_token`, `invite_code` e `certificate.code` são aleatórios
  (`Str::random()`/uuid), nunca sequenciais ou derivados do id
- Jurado só consulta submissão atribuída — a query parte de
  `judge_assignments`, nunca de listagem geral filtrada no front
- Resultado só fica público depois de `results_published_at`, checado no
  servidor em Policy

## 7. Histórico

| Data | Mudança |
|---|---|
| 2026-08-15 | Primeira versão, consolidando `/privacidade`, PLANO.md Anexo A.11 e `.claude/rules/security.md` — issue #84 |
