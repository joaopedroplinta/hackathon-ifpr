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

**Critério de pronto do sprint:** e-mail de deadline dispara sozinho no
horário — confirmado.

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
| 11.2 | Como organização, quero decidir onde hospedar o sistema mantendo o dado do participante no Brasil (LGPD), para publicar o site pro evento real. | 🔜 **Pendente** — opções levantadas (servidor IFPR/RNP, Vultr São Paulo, AWS `sa-east-1`/GCP `southamerica-east1`), nenhuma escolhida |
| 11.3 | Como usuário, quero microinterações mais polidas (loading de botão, transição entre páginas), para o sistema parecer tão profissional quanto o objetivo original pedia. | 🔜 Pendente |

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
