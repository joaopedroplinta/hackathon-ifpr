# Deploy de demonstração — Railway

Hospedagem **temporária**, só pra mostrar o sistema rodando fora do
localhost, com dado de ensaio (`DemoSeeder`). **Não é** a decisão de
hospedagem do evento real (PLANO.md §10 e Anexo A.10) — aquela ainda exige
dado no Brasil, e o Railway não tem região no país (mesma limitação que a
Render tinha). Substitui a demo anterior em Render + Supabase — ver
`CHANGELOG.md`.

**Diferença de LGPD em relação à demo anterior:** a demo na Render mantinha
o Postgres no Supabase em `sa-east-1` (São Paulo) mesmo com a aplicação fora
do Brasil. Esta migração move **tudo** pro Railway, inclusive o banco — o
dado de ensaio sai do Brasil por completo. Aceitável porque continua sendo
só `DemoSeeder` (nenhuma inscrição real), mas é uma regressão real em
relação ao setup anterior, registrada aqui de propósito.

**Não coloque inscrição real de participante aqui.** Dado de ensaio,
gerado pelo seeder, é o que existe nesse ambiente.

## Por que Railway em vez de Render

Render free tier não tinha worker nem Redis nem cron (mínimo Starter,
$7/mês cada) — a demo rodava com `QUEUE_CONNECTION=sync`, sem fila de
verdade. Railway agora é pago, então esta migração já sobe o setup
completo: worker de fila e agendador (`schedule:run`) como serviços de
verdade, com Redis — o mesmo `QUEUE_CONNECTION=redis` que dev já usa
(`.env.example`), em vez do `sync` improvisado da demo anterior.

## 1. Projeto Railway

Um projeto novo, com 5 recursos dentro dele:

| Recurso | Tipo | Papel |
|---|---|---|
| `Postgres` | Database (addon) | Banco — substitui o Supabase |
| `Redis` | Database (addon) | Fila (`queue:work`) e, se decidido depois, cache |
| `web` | Service (este repo) | `php artisan serve`, recebe tráfego HTTP |
| `worker` | Service (este repo) | `php artisan queue:work` |
| `scheduler` | Service (este repo) | `php artisan schedule:run`, via Cron Schedule do Railway |

`web`, `worker` e `scheduler` apontam pro **mesmo repositório e o mesmo
Dockerfile** — o que muda entre eles é só o **Custom Start Command** de
cada um (Settings → Deploy), igual ao antigo `startCommand` por serviço no
`render.yaml`.

### 1.1 Banco (addon Postgres)

1. No projeto, **New → Database → Add PostgreSQL**.
2. Não precisa copiar host/porta/senha à mão: o Railway expõe
   `${{Postgres.DATABASE_URL}}` como variável de referência, usada direto
   nos serviços abaixo.

### 1.2 Redis (addon)

1. **New → Database → Add Redis**.
2. Mesma lógica: `${{Redis.REDIS_URL}}` fica disponível como referência.

## 2. Variáveis compartilhadas

**Project Settings → Shared Variables** — equivalente ao `envVarGroups` do
`render.yaml`, um grupo só referenciado pelos três serviços:

```
APP_NAME=Hackathon IFPR
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stderr        # sem isso o log vai pro storage/logs/laravel.log,
                           # dentro do container efêmero — o Railway nunca
                           # vê nada no painel
APP_TIMEZONE=UTC
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_KEY=                  # gerar local com `php artisan key:generate --show`,
                           # colar o valor (base64:...) — nunca commitado
DB_CONNECTION=pgsql
DB_URL=${{Postgres.DATABASE_URL}}
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_URL=${{Redis.REDIS_URL}}
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=hackathon@ifpr.edu.br
MAIL_FROM_NAME=${{APP_NAME}}
RESEND_KEY=               # mesma chave "Hackathon IFPR" (Anexo A.10)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=      # preencher no passo 4, depois que a URL do web existir
GOOGLE_ALLOWED_DOMAIN=
FILESYSTEM_DISK=local
```

Cada serviço (`web`, `worker`, `scheduler`) referencia esse grupo em
Service Settings → Variables → **Add Shared Variable**, em vez de
redigitar tudo três vezes.

## 3. Serviço `web`

1. **New → GitHub Repo**, conectar `joaopedroplinta/hackathon-ifpr`,
   branch `main`. O Railway detecta o `Dockerfile` sozinho (e lê
   `railway.json` pra healthcheck e política de restart — não precisa
   configurar isso à mão).
2. Adicionar as Shared Variables do passo 2.
3. **Networking → Generate Domain** pra ganhar uma URL `*.up.railway.app`.
4. Voltar nas variáveis (agora só neste serviço, não precisa propagar pros
   outros) e preencher:
   - `APP_URL=https://<domínio gerado>`
   - `GOOGLE_REDIRECT_URI=https://<domínio gerado>/auth/google/callback`
5. No Google Cloud Console, adicionar essa mesma URL de callback em
   **Authorized redirect URIs** — sem isso, login com Google quebra mesmo
   com tudo certo do lado do Railway.
6. **Settings → Deploy → Wait for CI** — liga o equivalente ao "After CI
   Checks Pass" que a Render tinha: o Railway só builda depois que
   `ci.yml` (Pint, ESLint, Prettier, tsc, Pest) reportar sucesso pro commit.

CMD do `Dockerfile` já serve sem alteração: `storage:link` + `migrate
--force` + `php artisan serve --port ${PORT}` — o Railway injeta `$PORT`
automaticamente, igual a Render.

## 4. Serviço `worker`

1. No mesmo projeto, **New → GitHub Repo**, mesmo repositório
   `joaopedroplinta/hackathon-ifpr`, branch `main` — cria um segundo
   serviço apontando pro mesmo código.
2. Adicionar as mesmas Shared Variables do passo 2 (menos `APP_URL`/
   `GOOGLE_REDIRECT_URI`, que só o `web` usa).
3. **Settings → Deploy → Custom Start Command**:
   ```
   php artisan queue:work --tries=3 --max-time=3600
   ```
   Sem `storage:link`/`migrate` aqui — só o `web` roda isso, pra não ter
   dois serviços tentando migrar ao mesmo tempo no boot.
4. **Networking**: não gerar domínio — este serviço não recebe HTTP.

## 5. Serviço `scheduler`

1. Mesmo processo: terceiro serviço, mesmo repositório.
2. Mesmas Shared Variables (menos `APP_URL`/`GOOGLE_REDIRECT_URI`).
3. **Settings → Deploy → Custom Start Command**:
   ```
   php artisan schedule:run
   ```
4. **Settings → Cron Schedule**: `* * * * *` (a cada minuto — é assim que
   o agendador do Laravel funciona: `schedule:run` roda toda vez e decide
   sozinho se algo precisa disparar naquele minuto, não fica em loop).
   Com Cron Schedule configurado, o Railway sobe o container, roda o
   comando e derruba — não fica um processo vivo cobrando à toa.
5. Sem domínio aqui também.

## 6. Primeiro boot do banco e seed

Migration roda sozinha no boot do `web` (`migrate --force`, idempotente).
Seed continua manual, mas agora dá pra rodar direto pela CLI do Railway em
vez de precisar de um cliente Postgres externo:

```bash
railway login
railway link                                          # escolhe este projeto
railway run --service web php artisan db:seed --class=DemoSeeder --force
```

`DemoSeeder` cria um evento passado (encerrado, resultado publicado, 15
equipes) e o evento atual (inscrições abertas, 8 equipes já formadas).

## Limitações conhecidas dessa demo (aceitas de propósito)

- **Dado fora do Brasil.** Ver aviso no topo — regressão real em relação à
  demo anterior (Postgres saiu do Supabase `sa-east-1`). Só dado de ensaio.
- **Upload não sobrevive a redeploy.** `storage/app/private` fica no disco
  do container `web`, recriado a cada deploy. O Railway suporta Volumes
  persistentes (Settings → Volumes), mas não foi configurado aqui — fora de
  escopo desta migração, igual já era na demo Render.
- **Sem SPF/DKIM de domínio próprio.** Remetente continua
  `onboarding@resend.dev` (Anexo A.10) até alguém confirmar acesso ao DNS de
  `ifpr.edu.br`.
