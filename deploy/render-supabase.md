# Deploy de demonstração — Render + Supabase

Hospedagem **temporária**, só pra mostrar o sistema rodando fora do
localhost pra professora, com dado de ensaio (`EnsaioSeeder`). **Não é** a
decisão de hospedagem do evento real (PLANO.md §10 e Anexo A.10) — aquela
ainda exige dado no Brasil e nem Render nem a maior parte do Supabase têm
região aqui. Detalhe: o projeto Supabase deste guia foi criado em `sa-east-1`
(São Paulo) mesmo assim, então o Postgres ao menos fica no Brasil — só o
Render (aplicação) que não.

**Não coloque inscrição real de participante aqui.** Dado de ensaio,
gerado pelo seeder, é o que existe nesse ambiente.

## 1. Supabase (banco)

Projeto já criado: `hackathon-ifpr-demo`, região `sa-east-1`,
`db.rnbsokakhrhuawophyhq.supabase.co`, Postgres 17.

1. No painel do projeto → **Project Settings → Database → Connection
   string**, copiar o modo **Session pooler** (porta 5432, host
   `aws-0-sa-east-1.pooler.supabase.com`, IPv4 — o host de conexão direta é
   IPv6-only e a Render não alcança).
2. Guardar host, porta, usuário (`postgres.rnbsokakhrhuawophyhq`) e a senha
   do banco (definida na criação do projeto — se perdida, resetar em
   **Database → Reset database password**).

## 2. Render (blueprint)

Repositório já tem `render.yaml` + `Dockerfile` na raiz.

1. **New → Blueprint**, conectar o repositório GitHub
   `joaopedroplinta/hackathon-ifpr`, branch `main`.
2. A Render lê `render.yaml` e propõe 3 serviços, todos no free tier:
   `hackathon-demo-web` (site), `hackathon-demo-queue` (worker da fila) e
   `hackathon-demo-redis` (fila/cache). Aceitar a criação dos 3.
   - **Sem cron nessa demo.** `hackathon-demo-schedule` (`php artisan
     schedule:run`, o lembrete de prazo) não entrou no blueprint porque cron
     job não tem free tier na Render (mínimo $1/mês, cobrado por segundo
     rodando) — decisão de não gastar nisso numa demo temporária. Se algum
     dia precisar do agendador aqui, criar o serviço à mão com `plan:
     starter`.
3. Preencher no grupo de variáveis `hackathon-demo-env` (as marcadas
   `sync: false` no `render.yaml`, pedidas na criação do blueprint):
   - `APP_KEY` — gerar local com `php artisan key:generate --show` e colar
     o valor (`base64:...`), sem rodar o comando no Render
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — do
     passo 1
   - `RESEND_KEY` — a mesma chave "Hackathon IFPR" já criada (Anexo A.10)
   - `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — do Google Cloud Console
   - `GOOGLE_REDIRECT_URI` e `APP_URL` — só depois do passo 4, quando a URL
     `https://hackathon-demo-web.onrender.com` existir
4. Depois do primeiro deploy (vai falhar até o passo 5 rodar, e tudo bem):
   copiar a URL gerada pro `hackathon-demo-web`, voltar em
   `hackathon-demo-env` e preencher `APP_URL` e
   `GOOGLE_REDIRECT_URI=https://<url>/auth/google/callback`.
5. No Google Cloud Console, adicionar essa mesma URL de callback em
   **Authorized redirect URIs** (sem isso, login com Google quebra mesmo com
   tudo certo do lado da Render).

## 3. Primeiro boot do banco

Não existe CD além do CI de teste/lint (mesma lacuna do Anexo A.10) — migrar
e semear é manual, pelo **Shell** do serviço `hackathon-demo-web` no painel
da Render:

```bash
php artisan migrate --force
php artisan db:seed --class=EnsaioSeeder --force
```

`EnsaioSeeder` gera as 30 equipes e 5 jurados fictícios do ensaio geral —
dado suficiente pra mostrar todo o fluxo sem depender de ninguém se
cadastrar de verdade.

## Limitações conhecidas dessa demo (aceitas de propósito)

- **Upload não sobrevive a redeploy.** `storage/app/private` fica no disco
  do container, que a Render recria a cada deploy — arquivo de submissão
  enviado por essa demo some no próximo `git push`. Pra sobreviver, exigiria
  disco persistente ou trocar `FILESYSTEM_DISK` pra `s3` (não configurado
  aqui, fora de escopo desta demo).
- **Free tier hiberna.** O serviço web da Render (plano free) dorme depois
  de alguns minutos sem tráfego — primeiro acesso do dia demora ~30s pra
  acordar.
- **Sem SPF/DKIM de domínio próprio.** Remetente continua
  `onboarding@resend.dev` (Anexo A.10) até alguém confirmar acesso ao DNS de
  `ifpr.edu.br`.
