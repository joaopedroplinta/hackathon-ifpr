# Deploy em produção — Railway

Hospedagem **definitiva** do evento real (decidido em 2026-08-22, issue
#71 fechada). Começou como demo temporária (2026-08-17, Render + Supabase;
migrada por inteiro pro Railway em 2026-08-20) sob o critério de "dado tem
que ficar no Brasil" — a orientadora confirmou que hospedar fora do país
não é impeditivo pra este trabalho, o que resolve a questão e promove este
mesmo ambiente a definitivo, banco incluído.

**Diferença de LGPD em relação à demo anterior em Render:** aquela mantinha
o Postgres no Supabase em `sa-east-1` (São Paulo) mesmo com a aplicação
fora do Brasil. Esta migração move **tudo** pro Railway, inclusive o
banco — nenhum dado (nem de ensaio, nem de inscrição real) fica no Brasil.
Aceito pela orientadora, registrado aqui de propósito.

**Storage de upload já é persistente** (2026-08-22): Railway Volume
`hackathon-ifpr-volume` montado em `/var/www/html/storage/app/private` no
serviço `web` — um redeploy no meio do evento não apaga mais os arquivos
de submissão das equipes (ver seção "Volume de storage" abaixo). O que
ainda falta do Anexo A.10 do `PLANO.md` é só o agendamento de backup.

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
   branch `main`. O Railway detecta o `Dockerfile` sozinho e lê
   `railway.json` pra política de restart — mas **não** pra healthcheck:
   `railway.json` é compartilhado pelos três serviços deste repositório
   (`web`, `worker`, `scheduler`), e um `healthcheckPath` ali viraria
   herança automática pro `worker`/`scheduler` também, que não servem HTTP
   nenhum e nunca passariam no healthcheck (loop de crash já visto em
   produção — ver Épico 13 do backlog). Healthcheck é configurado só no
   dashboard deste serviço.
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
7. **Settings → Deploy → Healthcheck Path**: `/up`, **Healthcheck
   Timeout**: `300`. Só neste serviço — é dashboard, não `railway.json`.

CMD do `Dockerfile` já serve sem alteração: `storage:link` + `migrate
--force` + `php artisan serve --port ${PORT}` — o Railway injeta `$PORT`
automaticamente, igual a Render.

## 4. Serviço `worker`

1. No mesmo projeto, **New → GitHub Repo**, mesmo repositório
   `joaopedroplinta/hackathon-ifpr`, branch `main` — cria um segundo
   serviço apontando pro mesmo código.
2. Adicionar as mesmas Shared Variables do passo 2 (menos
   `GOOGLE_REDIRECT_URI`, que só o `web` usa) **e também `APP_URL`**, copiado
   do serviço `web` (Service → Variables → copiar o valor, não referenciar —
   `APP_URL` é literal, não uma Shared Variable). Sem isso, toda notificação
   processada pelo worker (verificação de e-mail, convite de equipe,
   resultado publicado, lembrete de prazo) monta a URL com `route()` sem
   contexto de request HTTP, que cai no default `http://localhost` do
   Laravel — bug real já visto em produção (Épico 13 do backlog).
3. **Settings → Deploy → Custom Start Command**:
   ```
   php artisan queue:work --tries=3 --max-time=3600
   ```
   Sem `storage:link`/`migrate` aqui — só o `web` roda isso, pra não ter
   dois serviços tentando migrar ao mesmo tempo no boot.
4. **Networking**: não gerar domínio — este serviço não recebe HTTP.

## 5. Serviço `scheduler`

1. Mesmo processo: terceiro serviço, mesmo repositório.
2. Mesmas Shared Variables (menos `GOOGLE_REDIRECT_URI`) **e também
   `APP_URL`**, pelo mesmo motivo do passo 2 do `worker`.
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

**Antes de abrir inscrição real, apagar qualquer dado de ensaio que já
tenha rodado neste ambiente enquanto ele era só demo** — equipe fictícia
do `DemoSeeder` misturada com equipe de verdade quebra ranking, contagem
de inscritos na landing e qualquer relatório tirado depois.

## 7. Volume de storage

Configurado em 2026-08-22, via CLI (o serviço `web` chama-se
`hackathon-ifpr` dentro do projeto Railway):

```bash
railway volume add --mount-path /var/www/html/storage/app/private   # cria o volume no serviço linkado
railway restart --service hackathon-ifpr --yes                      # aplica o mount (sem rebuild)
```

`/var/www/html/storage/app/private` é o `WORKDIR` do `Dockerfile` seguido
do caminho do disco `local` do Laravel — onde ficam regulamento e arquivo
de submissão. Sem o volume, esse diretório vive na camada gravável do
container e some a cada novo deploy; com ele, sobrevive.

Confirmar que pegou: nos logs do serviço (`railway logs --service
hackathon-ifpr`) deve aparecer `Mounting volume on: ...` antes do boot do
Laravel. Não montar o volume num caminho que ainda não existe na imagem —
o Railway cria o diretório, mas o dono/permissão do mount pode não bater
com o que o `Dockerfile` preparou (`chmod -R a+w storage`) se a imagem
mudar de usuário no futuro.

## Limitações conhecidas

- **Dado fora do Brasil.** Aceito formalmente pela orientadora em
  2026-08-22 (issue #71) — não é mais uma pendência, é a decisão tomada.
- **Sem SPF/DKIM de domínio próprio.** Remetente continua
  `onboarding@resend.dev` — decisão aceita (issue #78 fechada): verificar
  `ifpr.edu.br` exigiria acesso ao DNS institucional, fora do controle
  deste projeto.
