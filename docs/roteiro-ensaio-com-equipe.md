# Roteiro — ensaio geral com a equipe de organização

Pauta pra issue #86. Cobre os dois únicos itens do Anexo A.9 que sobraram
depois do rehearsal técnico de 2026-08-13 (issue #70) — os que exigem gente
de verdade na sala, não dá pra marcar sozinho no terminal:

1. Derrubar o app com equipes "submetendo" de verdade e confirmar que
   todas caem no Degrau 2 (Google Forms) sem ajuda
2. Ler o runbook (PLANO.md Anexo A.8) em voz alta com a equipe

**Duração:** ~45 min. **Local:** mesma sala/mesa que vai ser usada no dia
do evento, se possível — parte do ponto é testar o ambiente físico, não só
o sistema.

## Antes de começar

- [ ] Ambiente rodando: `docker compose up -d && php artisan serve` +
      `npm run dev` (ou a hospedagem real, se já decidida — issue #71)
- [ ] `php artisan db:seed --class=EnsaioSeeder` rodado, banco com as 30
      equipes de teste
- [ ] Google Forms do Degrau 2 (Anexo A.2) existe e está acessível — se
      ainda não foi criado, criar antes desta sessão é pré-requisito, não
      dá pra ensaiar um degrau que não existe
- [ ] Cada pessoa presente sabe seu papel no runbook: **Coordenação**,
      **Coord. técnico**, **Monitor** (papéis do Anexo A.8 — uma pessoa
      pode acumular mais de um se o time for pequeno, mas todos precisam
      estar claros sobre qual chapéu estão usando em cada exercício)
- [ ] Impresso (ou na tela, mas impresso é o ponto): o runbook (A.8), o
      texto do Degrau 0 do regulamento, e o link curto do Degrau 2

## Parte 1 — Leitura do runbook em voz alta (10 min)

Sem pressa, sem pular linha. O objetivo não é decorar — é cada pessoa
ouvir a própria voz dizendo a ação que vai tomar, uma vez, antes de
precisar tomar de verdade sob pressão.

1. Coordenação lê a regra de ouro em voz alta: **"uma pessoa declara
   incidente e anuncia. Se três pessoas dão instruções diferentes ao mesmo
   tempo, o problema técnico vira problema de confiança."**
2. Cada linha da tabela do runbook é lida por quem tem aquele papel —
   Coord. técnico lê as próprias linhas, Monitor lê as próprias:

   | Sintoma | Quem lê |
   |---|---|
   | Site não abre pra ninguém | Coord. técnico |
   | Site abre, upload falha | Monitor |
   | 1 equipe sem acesso | Monitor |
   | Rede caiu perto do deadline | Coordenação |
   | Banco corrompido | Coord. técnico |
   | Jurado sem acesso | Coordenação |

3. Pergunta aberta pro grupo: **alguém não sabe o que fazer na própria
   linha?** Se sim, para aqui e esclarece antes de continuar — não adianta
   simular incêndio se a leitura já revelou uma dúvida.

**Critério de pronto:** todo mundo presente já leu em voz alta pelo menos
uma linha que é responsabilidade sua.

## Parte 2 — Simular queda com gente de verdade (25 min)

Diferença do rehearsal técnico anterior: ali era uma pessoa rodando
comando no terminal. Aqui é gente de verdade, cada uma no próprio
celular/notebook, tentando de fato.

1. Escolher 3–5 pessoas do time (não precisam ser as "equipes de teste" do
   seed — podem literalmente ser colegas simulando participantes) pra
   tentar submeter um projeto pelo celular, ao mesmo tempo
2. Coord. técnico **derruba o app de propósito** — parar o `php artisan
   serve` (ou `docker compose stop` se estiver testando a hospedagem real)
   no meio da tentativa
3. Cronometrar: quanto tempo até alguém do grupo perceber que o site caiu
   e falar em voz alta?
4. Coordenação declara o incidente e anuncia o Degrau 2 — **em voz alta,
   como seria no evento**, não só mandando link no grupo do WhatsApp
5. As pessoas simulando participantes usam o link do Google Forms de
   verdade e preenchem: equipe, e-mail do líder, URL do repo, hash do
   commit
6. Depois de todas terem submetido pelo Forms, religar o app
   (`php artisan serve` de novo / `docker compose start`)
7. Alguém da organização baixa o CSV do Forms e roda:
   ```bash
   php artisan hackathon:import-submissions caminho/do/arquivo.csv --event=<slug> --source=form
   ```
8. Conferir no painel do organizador (`/admin/submissoes`) que as
   submissões do ensaio aparecem marcadas como `source = form` — a marca
   que existe justamente pra não confundir com envio direto pelo site
   (Anexo A.4)

**Critério de pronto:**
- [ ] Alguém do grupo, sem ser avisado com antecedência do horário exato,
      percebeu a queda sozinho
- [ ] O Degrau 2 foi divulgado em voz alta pela Coordenação, não só por
      mensagem
- [ ] Todas as submissões simuladas chegaram ao Forms
- [ ] O import trouxe todas pro sistema, marcadas como `source = form`,
      sem duplicar nada que já existia

## Depois da sessão

- [ ] Anotar aqui embaixo qualquer problema real encontrado (dúvida na
      leitura, degrau que não funcionou, papel sem dono) — se achou bug de
      código, vira issue como os três da #70; se foi falha de processo
      (ex.: ninguém sabia o link do Forms de cor), o ajuste é no próprio
      runbook ou no kit físico (Anexo A.7)
- [ ] Fechar a issue #86 com a data da sessão e o resultado

## Registro

| Data | Quem participou | Achados |
|---|---|---|
| [PREENCHER] | [PREENCHER] | [PREENCHER] |
