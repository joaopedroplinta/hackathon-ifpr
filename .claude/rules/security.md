# Regras — segurança e integridade

O sistema decide quem ganha um prêmio. Furo aqui não é bug, é contestação.

## Autorização

- Toda ação passa por **Policy**. `$this->authorize()` no controller ou
  `can:` na rota. Nunca `if ($user->role === 'organizador')` solto.
- **Jurado só enxerga o que foi atribuído a ele.** A query parte de
  `judge_assignments`, nunca de `Submission::all()` com filtro no front.
- Participante só acessa a própria equipe e a própria submissão.
- Resultado só é visível se `event.results_published_at` não for nulo —
  checado no servidor, em Policy, não escondendo o componente no React.

## Prazo

- `submission_deadline` comparado com `now()` **no servidor**, sempre.
- O contador regressivo no React é enfeite. Nunca é fonte de decisão.
- Extensão de prazo vem de `incidents`, vale pra todas as equipes, e fica
  registrada com autor e motivo.

## Upload

- Allowlist de mime type e extensão. Nunca denylist.
- Limite de tamanho explícito na Form Request.
- Nome do arquivo **gerado pelo sistema**. Nome original só guardado como
  metadado, nunca usado no caminho do disco.
- Storage em `storage/app/private/`, servido por rota autorizada. Nunca em
  `public/`.

## Dados

- `$fillable` explícito em todo model. Nunca `$guarded = []`.
- Campos que só o organizador muda (`status`, `final_score`, `rank`) ficam
  **fora** do `$fillable`.
- `invite_code`, `qr_token` e `certificate.code` gerados com
  `Str::random()`/uuid — nunca sequenciais ou derivados do id.
- Voto popular: unique `(event_id, user_id)` no banco. Validação só na
  aplicação perde a corrida com duplo clique.

## Auditoria

- Alteração de nota já submetida, desqualificação e publicação de resultado
  vão pro activity log com autor, horário e motivo.
- Submissão com `source != web` fica marcada até conferência.

## Segredos

- `.env` não é lido nem editado por aqui (negado em `settings.json`).
- Credencial nunca em código, seed, teste ou commit.
- Chave do Google OAuth só em variável de ambiente.
