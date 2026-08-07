# Configurar o login com Google

Leva ~10 minutos. Nada aqui é pago, e você não precisa de cartão.

> **Os nomes dos menus do Google mudam de tempos em tempos.** Se algo não
> estiver exatamente com o nome descrito, procure pela função — o fluxo é
> sempre: criar projeto → tela de consentimento → criar credencial OAuth.

## 1. Criar o projeto

<https://console.cloud.google.com> → seletor de projeto no topo → **Novo projeto**.

Nome: `hackathon-ifpr`. Não precisa de organização.

## 2. Tela de consentimento

Procure por **OAuth consent screen** / **Tela de consentimento OAuth**.

**Tipo: External / Externo.** Interno só funciona se o projeto estiver dentro
do Google Workspace do IFPR e você for administrador dele — o que
provavelmente não é o caso.

Preencha: nome do app (`Hackathon IFPR Pinhais`), e-mail de suporte, e-mail
do desenvolvedor. O resto pode ficar em branco.

**Escopos:** apenas `email` e `profile`. São não sensíveis, então o Google
**não** exige processo de verificação. Se alguém sugerir pedir mais escopo,
recuse — vira análise de semanas.

## 3. Usuários de teste — é aqui que todo mundo trava

Enquanto o app estiver em **Testing / Teste**, **só os e-mails cadastrados
como usuário de teste conseguem entrar.** Qualquer outro recebe um erro
genérico de acesso negado, sem dizer o motivo.

Duas saídas:

- **Durante o desenvolvimento:** adicione seu e-mail (e o de quem for testar)
  na lista de usuários de teste. Limite de 100.
- **Antes do evento:** clique em **Publicar app**. Como os escopos são só
  `email` e `profile`, a publicação é imediata — sem análise do Google.

**Publique antes do dia do hackathon.** Descobrir isso com 60 participantes
tentando entrar é o pior momento possível.

## 4. Criar a credencial

**Credentials / Credenciais** → **Criar credenciais** → **ID do cliente OAuth**
→ tipo **Aplicativo da Web**.

**URIs de redirecionamento autorizados** — precisa bater **exatamente**,
caractere por caractere:

```
http://localhost:8000/auth/google/callback
```

Detalhes que quebram silenciosamente:

- `http` em `localhost` é aceito pelo Google (é a exceção à regra do https)
- sem barra no final
- a porta faz parte do endereço: `:8000` é obrigatório se você usa
  `php artisan serve`
- quando houver produção, **adicione** a URL de lá também, sem remover a de
  desenvolvimento — o mesmo cliente aceita várias

## 5. Copiar para o `.env`

```env
GOOGLE_CLIENT_ID=algo.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-...
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Confira que `APP_URL` é `http://localhost:8000`, senão o `GOOGLE_REDIRECT_URI`
sai diferente do que você cadastrou.

```bash
php artisan config:clear
```

Pronto. Abra `/login` e clique em **Entrar com Google**.

## Restringir ao domínio institucional

```env
GOOGLE_ALLOWED_DOMAIN=ifpr.edu.br
```

Vazio (padrão) = qualquer conta Google. Preenchido = só aquele domínio.

**Pense antes de ligar:** jurado externo e mentor de empresa não têm e-mail
institucional. Com a restrição ativa eles caem no cadastro por e-mail e senha,
que continua funcionando — mas alguém precisa avisá-los disso.

## Erros comuns

| Mensagem | Causa |
|---|---|
| `redirect_uri_mismatch` | A URI não bate exatamente. Compare caractere por caractere, incluindo porta e barra final |
| Acesso bloqueado, app não verificado | O app está em Testing e o e-mail não está na lista de usuários de teste |
| `invalid_client` | `GOOGLE_CLIENT_ID` ou `SECRET` errado, ou faltou `config:clear` |
| Volta pro login com "sessão expirou" | `InvalidStateException` — cookie de sessão perdido. Confira `APP_URL` e se você não trocou de host no meio do fluxo (`localhost` vs `127.0.0.1` são hosts diferentes) |

## O que já funciona sem isso

Todo o resto. Cadastro por e-mail e senha, equipes, submissão — nada depende
do Google. A configuração pode esperar; só não pode esperar até a véspera.
