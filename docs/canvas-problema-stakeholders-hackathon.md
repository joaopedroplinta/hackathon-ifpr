**Kickoff do Projeto Integrador — Semana 2**

*Sistema de Apoio ao 1º Hackathon do Curso — IFPR Campus Pinhais*

Integrantes:  
Jair Rosa de Aguiar Neto  
João Pedro Camargo dos Santos  
João Pedro dos Santos Henrique Plinta

# Atividade 1 — Canvas de Definição do Problema

| Pergunta-guia | Resposta da equipe |
| :---- | :---- |
| **Quem é o stakeholder principal? Qual seu papel no Hackathon?** | A Comissão Organizadora do 1º Hackathon do curso, formada pela docente de Engenharia de Software I, um monitor da disciplina e dois representantes do Projeto de Extensão Azure DevOps IFPR (Marcelo e Renan, egressos do GTI). O papel da comissão é planejar e executar o evento — inscrições, comunicação, avaliação dos jurados e divulgação do resultado — sem ter formação nem tempo disponível para desenvolver um sistema próprio. |
| **Qual é o problema hoje (situação atual, "as-is")? Como o processo é feito atualmente, sem o sistema?** | As inscrições das equipes são feitas por planilha e e-mail, o que já gerou inscrições duplicadas e informações desencontradas. Não existe um canal para comunicar rapidamente mudanças de horário ou sala a todos os participantes. A avaliação dos jurados é feita em papel, e a apuração final leva horas, o que já gerou dúvidas sobre o resultado. Não há um local único onde participantes e público possam acompanhar o andamento e o resultado do evento. |
| **Por que isso é um problema? Que impactos e consequências ele gera?** | Gera retrabalho e risco de erro administrativo (duplicidade de inscrições), risco de equipes perderem atividades por falha de comunicação, apuração lenta e sujeita a contestação (afetando a credibilidade do resultado), baixa visibilidade do evento para quem não está participando, e sobrecarga de uma equipe organizadora pequena e voluntária, que não tem tempo para operar processos manuais complexos. |
| **Para quem mais isso é um problema, além do stakeholder principal? (participantes, jurados, público)** | Equipes participantes: risco de inscrição incorreta e de perder comunicados importantes. Jurados: processo de avaliação lento, manual e sujeito a erro. Coordenação do curso: interesse na imagem institucional do primeiro Hackathon do curso. Público/comunidade acadêmica: sem forma de acompanhar o evento em tempo real. |
| **Qual seria o cenário ideal ("to-be") depois que o sistema existir?** | Um sistema simples e centralizado onde: (1) equipes se inscrevem uma única vez, sem duplicidade; (2) mudanças de horário/sala são comunicadas instantaneamente a todos; (3) jurados registram notas digitalmente, com apuração automática e confiável; (4) qualquer pessoa acompanha publicamente o andamento e o resultado; (5) a equipe organizadora opera tudo sem precisar de treinamento extenso; (6) os dados pessoais dos participantes são tratados em conformidade com a LGPD. |
| **Que perguntas ainda estão em aberto e precisam ser validadas com a comissão?** | • Quais dados exatamente serão coletados de cada participante no cadastro (nome, e-mail, curso, RA, alergias/restrições alimentares)? • A avaliação dos jurados terá critérios/pesos definidos previamente, ou cada jurado atribui uma nota livre? • Como equipes incompletas ou com integrantes de outros cursos serão tratadas no cadastro? • O painel público de acompanhamento pode ser acessado sem login, ou exige autenticação? • Que canal de comunicação a comissão prefere para os avisos (notificação no sistema, e-mail, ambos)? • Haverá necessidade de exportar os dados de inscrição/resultado para uso institucional após o evento? |

# Atividade 2 — Mapa de Stakeholders

| Stakeholder | Papel no projeto | Interesse principal | Influência |
| :---- | :---- | :---- | :---- |
| **Comissão organizadora** | Cliente/stakeholder principal; define requisitos e valida entregas | Ter um sistema simples que resolva inscrição, comunicação, avaliação e divulgação de resultado, sem exigir tempo de aprendizado | Alta |
| **Equipes participantes** | Usuárias finais do sistema (inscrição e acompanhamento) | Inscrição confiável, informações atualizadas sobre horários/salas, acompanhar sua posição no evento | Média |
| **Jurados** | Usuários finais do sistema (avaliação) | Registrar notas de forma rápida, confiável e sem retrabalho manual | Média |
| **Coordenação do curso** | Interessada institucional, não opera o sistema diretamente | Sucesso e boa imagem do primeiro Hackathon do curso | Média |
| **Público / comunidade acadêmica** | Espectador/usuário do painel público | Acompanhar o andamento e o resultado do evento em tempo real | Baixa |
| **Turma de Engenharia de Software I** | Equipe de desenvolvimento do sistema | Entregar um sistema funcional dentro do prazo (Semana 19), cumprindo o entregável avaliado (peso 35%) | Alta |

# Atividade 3 — Perguntas para a Simulação de Entrevista

Perguntas preparadas para a rodada em que a outra equipe assume o papel da comissão organizadora, agrupadas por tema:

**Inscrições**

* Quais dados exatamente vocês precisam coletar no cadastro de cada participante (nome, RA, curso, e-mail, restrições alimentares)?

* Equipes podem ter integrantes de outros cursos do campus? Como isso deve ser identificado no cadastro?

**Comunicação**

* Quando uma mudança de horário ou sala acontece, quanto tempo a comissão tem para avisar as equipes, e qual canal preferem (notificação no sistema, e-mail, mural físico)?

**Avaliação e apuração**

* Os jurados vão avaliar com uma nota única ou critérios/pesos separados (ex.: inovação, viabilidade, apresentação)?

* Pode haver empate na apuração final? Como a comissão gostaria que o sistema tratasse esse caso?

**Painel público / divulgação**

* O painel de acompanhamento deve mostrar dados de todas as equipes durante todo o evento, ou só o resultado ao final?

**Operação e dados**

* Quem, dentro da comissão, vai ser responsável por operar o sistema no dia do evento (cadastrar horários, aprovar inscrições, etc.)?

* Que dado pessoal dos participantes é sensível o bastante para exigir cuidado extra de LGPD (ex.: alergias, dados de saúde)?