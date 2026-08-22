# Product Backlog — Sistema de Apoio ao 1º Hackathon IFPR Pinhais

Derivado do Canvas de Definição do Problema
([`canvas-problema-stakeholders-hackathon.md`](./canvas-problema-stakeholders-hackathon.md))
e das decisões registradas em `PLANO.md`. Organizado por épico, na ordem em
que foi construído (cronograma de 8 semanas).

Sem quadro Kanban aqui: o desenvolvimento já aconteceu, então este documento
registra o backlog **como executado** — cada história marcada com o status
real, não uma previsão. Cada uma virou uma issue no GitHub, fechada como
concluída ou aberta como pendente, agrupadas no projeto:
<https://github.com/users/joaopedroplinta/projects/9>.

Legenda: ✅ Concluído · 🔜 Pendente

---

## Épico 1 — Autenticação e Inscrição (Semana 1)

| # | História | Status |
|---|---|---|
| 1.1 | Como visitante, quero me cadastrar com e-mail e senha, para acessar o sistema sem depender de conta Google. | ✅ |
| 1.2 | Como visitante, quero entrar com minha conta Google institucional, para não precisar criar mais uma senha. | ✅ |
| 1.3 | Como usuário recém-cadastrado, quero confirmar meu e-mail antes de participar de qualquer equipe, para a organização conseguir me contatar de verdade. | ✅ |
| 1.4 | Como usuário autenticado, quero me inscrever no evento, para poder formar ou entrar em uma equipe. | ✅ |
| 1.5 | Como usuário, quero editar meu perfil e trocar minha senha, para manter meus dados corretos. | ✅ |

**Critério de pronto do sprint:** dá pra criar conta pelos dois caminhos e se
inscrever — confirmado.

---

## Épico 2 — Equipes (Semana 2)

| # | História | Status |
|---|---|---|
| 2.1 | Como participante inscrito, quero criar uma equipe, para começar a montar meu time. | ✅ |
| 2.2 | Como participante, quero convidar colegas por e-mail, para trazer quem ainda não tem conta no sistema. | ✅ |
| 2.3 | Como participante, quero entrar em uma equipe por código de convite, para não depender só de e-mail. | ✅ |
| 2.4 | Como líder de equipe, quero gerenciar membros (remover, transferir liderança), para a equipe nunca ficar órfã se eu sair. | ✅ |
| 2.5 | Como participante, quero sair da minha equipe, para corrigir um time formado errado antes do prazo. | ✅ |
| 2.6 | Como organizador, quero que entrada/saída de membro trave depois do fechamento das inscrições, para impedir mudança de time de última hora. | ✅ |

**Critério de pronto do sprint:** duas contas formam equipe do zero —
confirmado.

---

## Épico 3 — Submissões (Semana 3)

| # | História | Status |
|---|---|---|
| 3.1 | Como participante, quero preencher e enviar o formulário do projeto (título, resumo, links), para registrar minha submissão. | ✅ |
| 3.2 | Como participante, quero anexar arquivos ao projeto, para complementar a submissão além dos links. | ✅ |
| 3.3 | Como participante, quero reenviar o projeto quantas vezes precisar antes do prazo, para corrigir e completar sem medo de perder a versão anterior — cada envio vira uma versão nova, nada é sobrescrito. | ✅ |
| 3.4 | Como sistema, o prazo precisa ser comparado com o relógio do servidor, nunca do navegador, para ninguém burlar o prazo mudando a hora do próprio celular. | ✅ |
| 3.5 | Como organizador, quero ver submissões enviadas após o prazo marcadas como "atrasada" em vez de rejeitadas, para decidir cada caso em vez de perder uma equipe por segundos. | ✅ |
| 3.6 | Como organizador, quero listar e filtrar todas as submissões por trilha/status, para acompanhar quem ainda não enviou. | ✅ |

**Critério de pronto do sprint:** envio após o prazo cai como `late`, testado
— confirmado.

---

## Épico 4 — Agenda e Check-in (Semana 4)

| # | História | Status |
|---|---|---|
| 4.1 | Como visitante, quero ver a agenda pública com "acontecendo agora" destacado, para não perder atividade. | ✅ |
| 4.2 | Como visitante, quero exportar a agenda pro meu calendário (`.ics`), para ser avisado sem depender de olhar o site toda hora. | ✅ |
| 4.3 | Como organizador, quero cadastrar e editar itens da agenda, para manter tudo atualizado sem mexer em código. | ✅ |
| 4.4 | Como organizador, quero registrar presença escaneando o QR code do crachá, para o check-in ser rápido na entrada. | ✅ — marcada como concluída antes da hora na importação do backlog (só existiam a rota e a busca manual); leitor de QR pela câmera implementado de fato no PR #89 |
| 4.5 | Como organizador, quero confirmar presença por busca manual de nome, para o check-in funcionar mesmo se o QR falhar, o celular descarregar ou o crachá sumir. | ✅ |

**Critério de pronto do sprint:** escanear crachá registra presença —
confirmado.

---

## Épico 5 — Avaliação (Semana 5)

| # | História | Status |
|---|---|---|
| 5.1 | Como organizador, quero configurar critérios de avaliação com pesos diferentes, para a nota refletir o que realmente importa nesta edição. | ✅ |
| 5.2 | Como organizador, quero distribuir jurados automaticamente entre as submissões, balanceando a carga de cada um. | ✅ |
| 5.3 | Como organizador, quero declarar conflito de interesse entre jurado e equipe, para esse jurado nunca ser atribuído àquela submissão — bloqueio automático, não um aviso que dá pra ignorar. | ✅ |
| 5.4 | Como jurado, quero acessar só as submissões atribuídas a mim, para não ver nem poder mexer no trabalho de outro jurado. | ✅ |
| 5.5 | Como jurado, quero que minha nota seja salva automaticamente enquanto avalio, para não perder o trabalho se a rede cair no meio da avaliação. | ✅ |
| 5.6 | Como jurado, quero ver meu progresso ("7 de 12 avaliadas"), para saber quanto falta na minha fila. | ✅ |
| 5.7 | Como organizador, quero reatribuir em 1 clique a vaga de um jurado ausente, para o cálculo não travar por causa de quem faltou. | ✅ |

**Critério de pronto do sprint:** jurado avalia 3 projetos pelo celular sem
perder nota — confirmado.

---

## Épico 6 — Resultados e Voto Popular (Semana 6)

| # | História | Status |
|---|---|---|
| 6.1 | Como organizador, quero recalcular o resultado com um comando, para o ranking ficar congelado e auditável em vez de calculado na hora que alguém abre a página. | ✅ |
| 6.2 | Como organizador, quero publicar o resultado como uma ação explícita, separada do cálculo, para controlar exatamente quando ele fica público. | ✅ |
| 6.3 | Como visitante, quero ver o pódio geral e por trilha só depois da publicação, para o resultado nunca vazar antes da hora certa. | ✅ |
| 6.4 | Como participante inscrito, quero votar no projeto favorito (voto popular), com no máximo um voto por pessoa garantido pelo banco, não só pela aplicação. | ✅ |
| 6.5 | Como visitante, quero que a contagem do voto popular fique escondida durante a votação, para evitar efeito manada. | ✅ |

**Critério de pronto do sprint:** `hackathon:compute-results` bate com
planilha conferida na mão — confirmado, inclusive com conferência manual
registrada no ensaio geral (Semana 8).

---

## Épico 7 — Certificados e Notificações (Semana 7)

| # | História | Status |
|---|---|---|
| 7.1 | Como participante/jurado/organizador, quero baixar meu certificado em PDF, para comprovar minha participação. | ✅ |
| 7.2 | Como qualquer pessoa, quero validar um certificado por um código público (`/validar/{code}`), para confirmar que ele é legítimo sem depender de confiar cegamente num PDF. | ✅ |
| 7.3 | Como participante, quero receber um lembrete por e-mail perto do prazo de submissão, para não perder o prazo por esquecimento. | ✅ |
| 7.4 | Como organizador, quero que resultado publicado dispare notificação por e-mail, para ninguém precisar ficar recarregando a página. | ✅ |
| 7.5 | [Como organizador, quero definir quem assina o certificado em nome da comissão (nome e cargo),](https://github.com/joaopedroplinta/hackathon-ifpr/pull/123) para o documento ter a legitimidade de uma assinatura, como no certificado de referência que a comissão trouxe (CodeCon Summit). | ✅ |
| 7.6 | [Como participante, quero que meu CPF, matrícula e equipe/projeto apareçam no certificado que eu baixo,](https://github.com/joaopedroplinta/hackathon-ifpr/issues/120) para o documento ter validade legal — sem bloquear quem não preencheu CPF, e sem expor o CPF na página pública de validação. | ✅ |

**Critério de pronto do sprint:** e-mail de deadline dispara sozinho no
horário — confirmado.

**Pendente, fora deste sprint:** permitir que a comissão configure o
**template visual inteiro** do certificado (logo do evento, ilustração de
fundo, não só a assinatura) — 🔜
[#122](https://github.com/joaopedroplinta/hackathon-ifpr/issues/122). Item
maior, com decisões de design em aberto (upload de template vs. construtor
visual, sanitização de HTML customizado, versionamento entre edições).

---

## Épico 8 — Regulamento e Transparência

| # | História | Status |
|---|---|---|
| 8.1 | Como visitante, quero ler o critério de desempate e a regra de contingência do prazo antes de me inscrever, para saber exatamente como o resultado é decidido. | ✅ |
| 8.2 | Como organizador, quero anexar o PDF oficial do edital, para o regulamento formal ficar disponível sem duplicar conteúdo. | ✅ |

---

## Épico 9 — Plano B / Continuidade no Dia do Evento (Anexo A)

| # | História | Status |
|---|---|---|
| 9.1 | Como organizador, quero que "o horário do último commit" valha como comprovante de prazo, para a submissão sobreviver a uma queda de internet — regra mais barata e mais valiosa do plano B inteiro. | ✅ |
| 9.2 | Como organizador, quero importar submissões recebidas por um formulário de emergência externo (CSV), para regularizar quem usou o canal alternativo. | ✅ |
| 9.3 | Como organizador, quero lançar manualmente a submissão de uma equipe que não conseguiu acessar o sistema (papel ou e-mail), para ninguém ficar de fora por causa de infraestrutura. | ✅ |
| 9.4 | Como organizador, quero declarar um incidente com extensão de prazo que valha para todas as equipes, para nenhuma ser prejudicada por um problema que não foi culpa dela. | ✅ |
| 9.5 | Como organizador, quero rodar backup do banco e dos arquivos periodicamente durante o evento e restaurar rápido se algo corromper. | ✅ — backup ~0,3s, restore completo ~0,8s no dataset de ensaio (30 equipes), cronometrado |

---

## Épico 10 — Identidade Visual e Acessibilidade

| # | História | Status |
|---|---|---|
| 10.1 | Como qualquer usuário, quero uma interface com identidade própria do evento (não a aparência padrão do framework), para o sistema parecer profissional e institucional. | ✅ |
| 10.2 | Como usuário de celular, quero que as telas mais usadas no dia (avaliação, check-in, submissão) funcionem bem numa tela pequena, porque metade do uso real é no celular. | ✅ |
| 10.3 | Como usuário de leitor de tela, quero que menus e diálogos tenham descrição de acessibilidade e texto em português, para navegar o sistema sem depender só de visão. | ✅ |

---

## Épico 11 — Infraestrutura e Operação

| # | História | Status |
|---|---|---|
| 11.1 | Como organização, quero rodar um ensaio geral com dados e carga realistas antes do evento, para achar problema antes que ele apareça na frente de 25 equipes. | ✅ — achou e corrigiu 3 bugs reais (queda da página de resultados, corrupção de fuso horário no import de emergência, quebra na validação de certificado) |
| 11.2 | Como organização, quero decidir onde hospedar o sistema mantendo o dado do participante no Brasil (LGPD), para publicar o site pro evento real. | ✅ — [#71](https://github.com/joaopedroplinta/hackathon-ifpr/issues/71) fechada em 2026-08-22: a professora orientadora confirmou que hospedar fora do Brasil não é impeditivo pra este trabalho, relaxando a exigência original. Segue no Railway (`deploy/railway.md`) |
| 11.3 | Como usuário, quero microinterações mais polidas (loading de botão, transição entre páginas), para o sistema parecer tão profissional quanto o objetivo original pedia. | ✅ — toast real (sonner), loading de botão em todo formulário com `processing`, transição de página. Depois retomado e ampliado numa segunda passada: identidade visual inteira reconstruída (PR #100, ver `PLANO.md` §11) |
| 11.4 | Como visitante, quero ver um aviso de cookies com a opção de aceitar ou recusar, para saber o que o sistema guarda no meu navegador antes de continuar usando. | ✅ |
| 11.5 | Como organização, quero avaliar trocar o driver de filas (e possivelmente cache) de `database` para Redis, para reduzir carga no Postgres no pico de uso do dia do evento. | ✅ — trocado, com persistência e systemd configurados |
| 11.6 | Como organização, quero um provedor de e-mail transacional real configurado em produção, para convite de equipe, lembrete de prazo, resultado publicado, verificação de conta e reset de senha chegarem de verdade. | ✅ — [#78](https://github.com/joaopedroplinta/hackathon-ifpr/issues/78) fechada em 2026-08-22: Resend configurado via variável de ambiente, e-mail real confirmado chegando (verificação de conta testada de ponta a ponta em produção). SPF/DKIM de domínio próprio ficou fora de escopo — remetente é o sandbox `onboarding@resend.dev`, verificar `ifpr.edu.br` exigiria acesso ao DNS institucional fora do controle deste projeto; decisão foi aceitar o sandbox pro caso de uso atual |
| 11.7 | Como organização, quero formalizar a nomeação do Encarregado de Proteção de Dados (DPO) em documento institucional, para o papel exigido pela LGPD (art. 41) não existir só como e-mail de contato na página `/privacidade`. | 🔜 [#83](https://github.com/joaopedroplinta/hackathon-ifpr/issues/83) — minuta pronta em `docs/termo-designacao-dpo.md`, falta o nome de quem assume o papel e o registro formal (portaria/ata). Decisão organizacional, não é tarefa de código |
| 11.8 | Como organização, quero montar o RoPA (Registro das Operações de Tratamento de Dados Pessoais), para ter o artefato de governança que a LGPD espera além da política de privacidade voltada ao usuário final. | ✅ — [`docs/ropa.md`](./ropa.md) |
| 11.9 | Como organização, quero testar o caminho de conflito do import de CSV de emergência (Degrau 2) com um caso proposital, para confirmar que o sistema nunca sobrescreve uma submissão existente sem decisão humana. | ✅ |
| 11.10 | Como organização, quero rodar o ensaio com a equipe de organização presente, para validar os itens do Anexo A.9 que não são testáveis por código nem por uma pessoa sozinha no terminal (derrubar o app com submissão concorrente de verdade, ler o runbook em voz alta com a equipe). | 🔜 [#86](https://github.com/joaopedroplinta/hackathon-ifpr/issues/86) — exercício social, precisa da equipe de organização presente |

**O que falta no projeto inteiro, hoje, são só estas duas pendências —
ambas do Épico 11, ambas decisão organizacional ou operacional, nenhuma
exige código novo:** nomeação formal do DPO (#83) e o ensaio ao vivo com a
equipe (#86). Hospedagem (#71) e provedor de e-mail (#78) foram fechadas em
2026-08-22.

---

## Épico 12 — Identidade Institucional

Não fazia parte do cronograma original de 8 semanas — proposto depois, pela
comissão organizadora, junto do ensaio geral.

| # | História | Status |
|---|---|---|
| 12.1 | [Como usuário, quero informar CPF, vínculo institucional e matrícula (SUAP/SIAPE) no meu perfil,](https://github.com/joaopedroplinta/hackathon-ifpr/issues/101) para o certificado emitido ter validade legal e o vínculo institucional ser confirmável quando declarado. | ✅ — PR #103, mesclada |
| 12.2 | [Como usuário, quero trocar minha foto de perfil,](https://github.com/joaopedroplinta/hackathon-ifpr/issues/102) para minha conta ser reconhecível por colegas de equipe, jurados e organização mesmo sem entrar pelo Google. | ✅ — PR #103, mesclada. De quebra corrigiu um bug real: `auth.user.avatar` nunca funcionava (a coluna é `avatar_url`), então nenhuma foto — nem a do Google — jamais aparecia no header/sidebar |

**Resolvido:** o certificado agora mostra CPF e matrícula (quando
preenchidos) e nunca bloqueia a emissão de quem não tem CPF cadastrado — ver
Épico 7, item de certificados
([#120](https://github.com/joaopedroplinta/hackathon-ifpr/issues/120)).

---

## Épico 13 — Gestão de Usuários e Papéis

Não fazia parte do cronograma original de 8 semanas — faltava desde o
início: conceder qualquer papel (jurado, organizador, admin) só era possível
via seeder/tinker, nunca por uma tela. `PLANO.md` §3 já descrevia essa
capacidade ("admin: Tudo + gerenciar usuários e papéis"), mas nunca tinha
sido construída.

| # | História | Status |
|---|---|---|
| 13.1 | [Como admin, quero conceder ou remover papel de outro usuário por uma tela,](https://github.com/joaopedroplinta/hackathon-ifpr/issues/110) para não depender de acesso ao banco/tinker pra promover alguém a jurado, organizador ou admin. | ✅ |

**Critério de pronto do sprint:** admin concede papel pela tela; organizador
não vê o link nem acessa a rota direto (403); admin não consegue remover o
próprio papel de admin — confirmado.

---

## Rastreabilidade com o Canvas de Descoberta

Os quatro épicos que respondem diretamente às dores relatadas pela comissão
no briefing da Semana 2:

| Dor relatada pela comissão | Épico que resolve |
|---|---|
| "Inscrições por planilha e e-mail... inscrição duplicada" | Épico 1 (Autenticação e Inscrição) + Épico 2 (Equipes) |
| "Não temos como comunicar mudanças de horário ou sala rapidamente" | Épico 4 (Agenda e Check-in) |
| "Avaliação em papel; apuração demora horas e já gerou dúvida" | Épico 5 (Avaliação) + Épico 6 (Resultados) |
| "Não temos um lugar único onde todo mundo possa acompanhar" | Épico 4, 6, 7, 8 (todas as páginas públicas) |
| "Equipe pequena e voluntária, sem tempo pra sistema complicado" | Épico 10 (Acessibilidade/mobile) — celular é metade do uso real no dia |
