# Revisão de interface — evidências para o Codex

Acompanha a implementação de `docs/plano-repaginada-completa.md`. Uma linha
por página das 49 listadas na seção 5 do plano. Preenchido durante o
trabalho, não depois — status "pendente" é o padrão até verificação real.

**Ambiente de verificação:** `docker compose up -d`, `php artisan migrate`,
`npm run dev`, `php artisan serve` (subiu em `127.0.0.1:8017` nesta sessão —
porta 8000 ocupada por processo externo), `php artisan queue:work`. Banco
com dados do `DemoSeeder` (evento passado encerrado + evento atual com
inscrições abertas). Contas de teste: ver seção "Contas" no fim deste
arquivo.

**Limitação de ambiente registrada — não contornada:** o `resize_window` do
navegador automatizado desta sessão não altera a janela real (`window.innerWidth`
permanece 1920px após qualquer chamada), e não há emulação de dispositivo
exposta pela ferramenta. **Não foi possível capturar screenshot real em
320/390/768px nesta sessão.** Onde a coluna "Validação" diz "código", a
verificação de responsividade foi feita por leitura das classes Tailwind
(mobile-first, breakpoints `sm/md/lg`, `overflow-x-auto`, alvo de toque
`size-11`/`h-11`/`min-h-11`), não por captura visual. Isso não substitui
verificação visual real em 320/390px — pendente de ambiente com emulação de
dispositivo funcional (browser local do usuário, ou outra sessão com esse
suporte).

Legenda de status de implementação: `feito` (revisado e ajustado nesta
tarefa), `ja-conforme` (auditado, já seguia o padrão, sem mudança de
conteúdo necessária), `pendente` (ainda não auditado).

Legenda de validação: `navegador` (interação real verificada nesta sessão,
desktop 1920px, claro e escuro), `código` (revisão de classes/lógica, sem
captura visual), `pendente`.

## Componentes compartilhados (seção 4 do plano)

Criados em `resources/js/components/hackathon/`:

- `pagina.tsx` — `ContainerPagina` (larguras `formulario|leitura|operacao|painel`) e `CabecalhoPagina` (eyebrow + título + descrição + ação).
- `estado-vazio.tsx` — `EstadoVazio` (ícone, título, descrição, ação opcional).
- `status.tsx` — `Status` (selo ícone+texto, 5 tons; nunca só cor).
- `secao-formulario.tsx` — `SecaoFormulario` (título + instrução + campos).
- `paginacao.tsx` — `Paginacao` (links do Laravel paginator, alvo de toque 44px).
- `resumo-erro.tsx` — `ResumoErro` (banner de erro de formulário para leitor de tela).
- `confirmar-acao.tsx` — `ConfirmarAcao` (Dialog de confirmação para ação sensível/destrutiva).

Ainda não aplicados a nenhuma página existente — serão adotados conforme o
lote correspondente for auditado, não retroativamente em massa (evita
"marcar pronto só porque o layout pai mudou", vedado pela seção 9).
Validação: `código` (compilam, `tsc`/`eslint` limpos); sem tela própria para
screenshot.

## Correção transversal já aplicada

- **Bug do skip link do cabeçalho público corrigido.** `cabecalho-publico.tsx`
  apontava `#conteudo-publico` para um `<span>` vazio logo após o header, não
  para o conteúdo real (item citado no plano, seção 5, Lote F). Removido o
  span; cada página pública agora carrega `id="conteudo-publico" tabIndex={-1}`
  no próprio `<main>`. Verificado via teclado nesta sessão: `Tab` → `Enter`
  no link "Pular para o conteúdo" move `document.activeElement` para o
  `<main>` real de `publico/inicio.tsx`. Validação: `navegador`.

## Lote A — Público (10/10 auditadas)

| Página | Papel/rota | Estados vistos | Arquivos alterados | Implementação | Validação |
|---|---|---|---|---|---|
| `publico/inicio.tsx` | `home` | evento publicado c/ inscrições abertas, não-inscrito | Ajustado texto do passo 3 (vídeo não podia parecer obrigatório — inconsistente com "(opcional)" em `submissao/minha.tsx`); `id`/`tabIndex` no `<main>` | feito | navegador (desktop claro/escuro, skip link, sem erro de console); mobile: código |
| `publico/agenda.tsx` | `agenda.index` | com itens (evento atual sem agenda ainda — não testado com itens reais) | `id`/`tabIndex` no `<main>` | feito | código (ícone/hora/trilha/estado "agora" e vazio já conformes na leitura; não exercitado ao vivo com itens) |
| `publico/projetos.tsx` | `projetos.index` | sem itens (evento atual ainda sem submissão) | `id`/`tabIndex`; adicionada explicação pré-voto ("só 1 voto, não dá pra trocar" — confirmado contra `CastPopularVote`, que não tem update/destroy); adicionado estado "votação não aberta" que faltava (antes só cobria aberta-sem-permissão) | feito | código (estado vazio exercitado ao vivo; voto/já-votado/fechada não exercitados com dado real) |
| `publico/rubrica.tsx` | `rubrica.show` | com critérios (evento piloto) | `id`/`tabIndex` no `<main>` | feito | código |
| `publico/regulamento.tsx` | `regulamento.show` | — | `id`/`tabIndex` no `motion.main` | feito | código |
| `publico/resultados.tsx` | `resultados.show`, `resultados.show.edicao` | — | `id`/`tabIndex` no `<main>` | feito | código |
| `publico/edicoes.tsx` | `edicoes.index` | — | `id`/`tabIndex` no `<main>` | feito | código |
| `publico/validar.tsx` | `certificates.validate` | — | `id`/`tabIndex` no `<main>` | feito | código |
| `publico/privacidade.tsx` | `privacidade.show` | — | `id`/`tabIndex` no `motion.main`; conteúdo jurídico não tocado | feito | código |
| `publico/cookies.tsx` | `cookies.show` | — | `id`/`tabIndex` no `motion.main`; conteúdo não tocado | feito | código |

**Pendente do Lote A antes de fechar de verdade:** exercitar `agenda.tsx` com
itens reais de agenda (evento piloto tem `schedule_items`? conferir), exercitar
`projetos.tsx` com voto e resultado publicado, capturar 320/390/768px em
ambiente com emulação de dispositivo funcional, e testar o download `.ics`
de fato abre/importa.

## Lotes B a F (39/49) — pendentes

Ainda não auditados nesta sessão: `auth/*` (6), `settings/*` (3, Lote B);
`dashboard.tsx` (já tocado na etapa anterior, falta revisão desta tarefa),
`inscricao/criar.tsx`, `equipe/*` (4), `submissao/minha.tsx`,
`credencial/mostrar.tsx`, `certificados/index.tsx` (Lote C);
`jurado/avaliar.tsx` (Lote D, `jurado/fila.tsx` já tocado na etapa anterior);
os 18 itens do Lote E (`admin/*`); `errors/erro.tsx` e os componentes
transversais do Lote F.

Continuação planejada na ordem da seção 6 do plano (jornada do
participante → dia do evento → gestão restante → fechamento).

## Regressão funcional (seção 8.2) — não executada ainda

Nenhum dos 6 fluxos da seção 8.2 foi percorrido ponta a ponta nesta sessão.

## Comandos de verificação

```
npx tsc --noEmit        # limpo após componentes + Lote A
npm run lint:check      # limpo após componentes + Lote A
```

Ainda não executados nesta sessão: `./vendor/bin/pint --test`,
`npm run format:check`, `npm run build`, `./vendor/bin/pest`,
`git diff --check`.

## Contas de teste (banco local, senha `password`)

admin `joaopedrohenriqueplinta@gmail.com` · organizador
`organizacao@ifpr.edu.br` · jurado `janaina11@example.org` · participante-líder
`wchaves@example.org`.
