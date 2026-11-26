# Manual do usuário — Hackathon IFPR

Guia de uso do sistema para cada perfil, com um glossário das situações e
mensagens que aparecem na tela. Versão do sistema: 1.3.5 (13/11/2026).
Requisitos de navegador e dispositivo estão no
[README](../README.md#requisitos-para-usar).

## Sumário

1. [Antes de começar](#1-antes-de-começar)
2. [Visitante](#2-visitante)
3. [Participante](#3-participante)
4. [Jurado](#4-jurado)
5. [Organização](#5-organização)
6. [Administração](#6-administração)
7. [Glossário de situações](#7-glossário-de-situações)
8. [Mensagens de erro e o que fazer](#8-mensagens-de-erro-e-o-que-fazer)

## 1. Antes de começar

- **Criar conta:** em "Cadastrar", com e-mail e senha, ou em "Entrar com
  Google". Quem se cadastra com e-mail recebe um link de confirmação e só
  usa o sistema depois de confirmar.
- **Perfil:** em Configurações ficam nome, e-mail, foto, CPF, vínculo com o
  IFPR (aluno, professor ou externo) e matrícula. Nenhum desses dados é
  obrigatório para usar o sistema, mas o CPF e a matrícula saem no
  certificado.
- **Tutorial guiado:** as telas principais têm um passo a passo que explica
  cada parte da página. Ele abre na primeira visita e pode ser reaberto pelo
  botão "Ver o tutorial desta tela".
- **Tema:** claro, escuro ou o do sistema, em Configurações › Aparência.

## 2. Visitante

Sem login, é possível ver:

| Página | O que mostra |
| --- | --- |
| Início (`/`) | o tema da edição, as datas e como participar |
| Agenda (`/agenda`) | a programação, o que está acontecendo agora e o arquivo `.ics` para a agenda do celular |
| Regulamento (`/regulamento`) | regras, tamanho de equipe, prazo, desempate e o PDF do edital, quando houver |
| Rubrica (`/rubrica`) | os critérios de avaliação e seus pesos |
| Projetos (`/projetos`) | a vitrine das submissões, depois que o resultado é publicado |
| Resultados (`/resultados`) | pódio geral, pódio por trilha e prêmio popular, depois da publicação |
| Validar certificado (`/validar/{código}`) | se um certificado é autêntico |

## 3. Participante

1. **Inscrição no evento:** no painel, "Inscrever-se". Informe telefone,
   curso; tamanho de camiseta e restrições alimentares só quando a edição pede.
2. **Equipe:** em Equipe, crie uma nova ou entre com o código de convite
   que o líder passou. O líder também pode convidar por e-mail. O tamanho
   mínimo e máximo vêm do regulamento. O líder que quiser sair transfere a
   liderança antes.
3. **Crachá:** em Credencial fica o QR Code pessoal. Mostre-o à organização
   na entrada e nas oficinas. Sem câmera, a organização busca pelo nome.
4. **Submissão:** em Submissão, o líder preenche título, descrição, links do
   repositório e do vídeo e anexa arquivos. "Salvar rascunho" guarda sem
   enviar; "Enviar" registra uma versão. Cada novo envio vira outra versão,
   e as anteriores continuam no histórico. O prazo é o do servidor; o
   contador na tela é só uma referência. Um envio depois do prazo fica como
   "Enviado fora do prazo" até a organização decidir.
5. **Voto popular:** quando a votação abre, cada pessoa vota em um projeto,
   uma única vez.
6. **Certificados:** em Certificados, depois que a organização emitir. Cada
   certificado tem um código que qualquer pessoa confere em `/validar`.

## 4. Jurado

1. Em Jurado aparece a fila com as submissões atribuídas a você, e só elas.
   Equipes com quem você tem conflito de interesse registrado não entram na
   sua fila.
2. Em "Avaliar", o projeto fica de um lado e a rubrica do outro. Cada nota é
   salva sozinha como rascunho ("Rascunho salvo.") enquanto você preenche.
3. "Enviar avaliação" fecha a nota. Depois disso, só a organização pode
   reabrir, e a reabertura fica registrada com o motivo.
4. "Próxima pendente" leva à próxima submissão sem nota.

## 5. Organização

| Área | Para que serve |
| --- | --- |
| Painel | inscritos, equipes sem submissão, jurados atrasados e presença do dia |
| Evento | nome, tema, datas, limites de equipe, regulamento em PDF e visual do certificado |
| Equipes | listar, desclassificar com motivo e reabilitar |
| Submissões | todas as submissões, filtros, exportação, decisão sobre envio atrasado e lançamento manual (plano B) |
| Rubrica | critérios, pesos e qual rubrica está ativa |
| Jurados | distribuição automática, ajuste manual, reatribuição, conflitos de interesse e reabertura de avaliação |
| Agenda | criar, editar e publicar itens da programação |
| Check-in | checkpoints (entrada, oficinas) e leitura do QR Code, com busca manual |
| Incidentes | declarar incidente com extensão de prazo, que vale para todas as equipes |
| Resultados | recalcular, conferir e publicar. Publicar é sempre uma ação explícita |
| Certificados | "Emitir certificados do evento" emite o lote; a emissão avulsa serve para mentoria ou correção |

O lote de certificados segue estas regras: participação para quem teve
presença registrada, jurado para quem enviou avaliação, organização para a
comissão e colocação para o pódio, só depois da publicação do resultado.
Emitir de novo não duplica nenhum certificado.

## 6. Administração

Tudo o que a organização faz, mais a tela Usuários: busca de pessoas e
troca de papéis. Quem vira jurado, organizador ou administrador deixa de ser
participante. O histórico (inscrição, equipe, submissão, avaliação e
certificado) nunca é apagado quando um papel muda.

## 7. Glossário de situações

| Termo na tela | Significado |
| --- | --- |
| Rascunho (submissão) | salva, mas ainda não enviada; não concorre |
| Enviado | enviada dentro do prazo; concorre |
| Enviado fora do prazo | chegou depois do prazo e aguarda decisão da organização |
| Fora do prazo — aceito | a organização aceitou o envio atrasado; concorre |
| Fora do prazo — recusado | a organização recusou o envio atrasado; não concorre |
| Desclassificado(a) | retirado(a) da competição pela organização, com motivo registrado |
| Em formação (equipe) | equipe criada, ainda montando o time |
| Confirmada (equipe) | equipe pronta para competir |
| Ativo / Saiu da equipe | situação de cada integrante |
| Não iniciada / Em andamento / Concluída | andamento da avaliação de um jurado |
| Rascunho / Enviada (avaliação) | nota salva sem fechar / nota fechada |
| Rascunho / Publicado / Em andamento / Encerrado | fases do evento |
| Trilha | categoria da solução dentro do tema (por exemplo, Saúde ou Educação), com pódio próprio |
| Rubrica | conjunto de critérios com peso usado para calcular a nota |
| Checkpoint | ponto de presença, como a entrada ou uma oficina |
| Incidente | registro de um problema no evento, que pode estender o prazo para todos |
| Plano B | alternativas de envio quando o sistema falha: link do repositório, formulário externo, e-mail ou papel |

## 8. Mensagens de erro e o que fazer

| Mensagem | O que fazer |
| --- | --- |
| Acesso não autorizado | a página é de outro perfil. Volte ao painel; se deveria ter acesso, fale com a organização |
| Página não encontrada | o endereço não existe ou foi removido. Volte ao início |
| Algo deu errado | erro do sistema, não seu. Tente de novo em instantes; se repetir, avise a organização |
| Sistema em manutenção | aguarde alguns minutos e tente de novo |
| Tentativas demais. Aguarde N segundos | proteção contra abuso. Espere o tempo indicado |
| O campo X é obrigatório | preencha o campo indicado; o erro aparece logo abaixo dele |
| Confirme seu e-mail | abra o link enviado por e-mail ou peça um novo na tela de confirmação |
