/**
 * Catálogo dos tutoriais guiados, um por página interna (chave = nome do
 * componente Inertia). Cada tela só é alcançável pelo papel certo (Policy),
 * então o papel não precisa ser repetido aqui.
 *
 * `target` é o valor de `data-tour` no elemento. Itens do menu lateral usam
 * `nav:<url>`. `titulo` é o `h1` da página (sem atributo: toda página tem um).
 * Alvo ausente na tela (menu que o papel não vê, lista vazia, menu recolhido
 * no celular) faz o passo ser pulado -- nunca quebra.
 *
 * Todo texto aqui descreve o que a tela de fato mostra ou faz: ao mudar uma
 * página, confira o tutorial dela. `id` é estável e é o que fica salvo em
 * `users.tours_seen`; mudou o conteúdo de forma relevante? Sufixe o id
 * (`.v2`) para o tour reaparecer. Formato aceito pelo servidor:
 * /^[a-z0-9.\-]{1,64}$/.
 */
export type TourStep = { target: string; title: string; description: string };
/** `manual`: nunca abre sozinho (telas de uso crítico no dia do evento); só pelo botão. */
export type TourDefinition = { id: string; manual?: boolean; steps: TourStep[] };

const conteudo = (title: string, description: string): TourStep => ({ target: 'titulo', title, description });
const menu = (url: string, title: string, description: string): TourStep => ({ target: `nav:${url}`, title, description });
const passo = (target: string, title: string, description: string): TourStep => ({ target, title, description });

export const tours: Record<string, TourDefinition> = {
    dashboard: {
        id: 'dashboard',
        steps: [
            conteudo(
                'Bem-vindo ao Hackathon IFPR',
                'Esta é a sua página inicial, com o resumo da sua participação e atalhos para o que você usa no evento.',
            ),
            menu('/equipe', 'Minha equipe', 'Crie uma equipe ou entre em uma com o código de convite.'),
            menu('/submissao', 'Meu projeto', 'Envie a descrição e os arquivos do projeto antes do prazo.'),
            menu('/credencial', 'Crachá', 'Seu QR Code para o check-in no dia do evento.'),
            menu('/jurado', 'Avaliar', 'Os projetos atribuídos a você ficam aqui.'),
            menu('/painel', 'Painel de organização', 'Pendências, equipes, agenda, check-in e resultados da edição.'),
            menu('/certificados', 'Certificados', 'Seus certificados aparecem aqui, geralmente depois do evento.'),
        ],
    },
    'equipe/minha': {
        id: 'equipe.minha',
        steps: [
            conteudo('Sua equipe', 'Aqui você vê os integrantes e a situação da equipe.'),
            passo('convite', 'Código de convite', 'Quem tiver este código entra na equipe. Compartilhe com os colegas e use o botão Copiar.'),
            passo(
                'equipe',
                'Integrantes',
                'Veja quem já está na equipe e quantos faltam para o mínimo. Quem lidera pode convidar, editar e submeter pela equipe.',
            ),
            menu('/submissao', 'Próximo passo', 'Com a equipe formada, envie o projeto em "Meu projeto".'),
        ],
    },
    'equipe/sem-equipe': {
        id: 'equipe.sem-equipe',
        steps: [
            conteudo('Equipe', 'Seu projeto começa com uma equipe: crie uma nova ou entre em uma que já existe.'),
            passo('acao', 'Criar ou entrar', 'Crie uma equipe e convide outras pessoas, ou entre com o código de convite que você recebeu.'),
        ],
    },
    'submissao/minha': {
        id: 'submissao.minha',
        steps: [
            conteudo('Projeto da equipe', 'Aqui a equipe registra o projeto. O contador mostra quanto falta para o prazo de envio.'),
            passo(
                'formulario',
                'Dados do projeto',
                'Título, resumo e repositório são essenciais. Vocês podem salvar rascunho quantas vezes quiserem, mas só o envio conta para a avaliação.',
            ),
        ],
    },
    'credencial/mostrar': {
        id: 'credencial.mostrar',
        steps: [
            conteudo(
                'Seu crachá digital',
                'Mostre este código para a organização em cada entrada e oficina. É pessoal e não muda durante o evento. Se a câmera não ler, peça para buscarem seu nome na lista.',
            ),
        ],
    },
    'certificados/index': {
        id: 'certificados.index',
        steps: [
            conteudo('Certificados', 'Documentos emitidos pela organização, geralmente depois do evento.'),
            passo('lista', 'Seus certificados', 'Baixe o PDF de cada um. Se aparecer "Gerando o PDF…", aguarde um instante.'),
        ],
    },
    'jurado/fila': {
        id: 'jurado.fila',
        steps: [
            conteudo('Fila de avaliação', 'Aqui ficam só as submissões atribuídas a você, com o progresso das suas avaliações.'),
            passo(
                'lista',
                'Submissões atribuídas',
                'Abra uma para avaliar. Cada uma aparece como Pendente ou Avaliada, e dá para salvar rascunho e continuar depois.',
            ),
        ],
    },
    'jurado/avaliar': {
        id: 'jurado.avaliar',
        manual: true,
        steps: [
            conteudo('Avaliar projeto', 'Atribua uma nota a cada critério. A nota zero é válida: deixe o campo vazio só enquanto não decidir.'),
            passo(
                'resumo',
                'Resumo do projeto',
                'Título, resumo, descrição e links da equipe (repositório, vídeo e aplicação) para consultar enquanto avalia.',
            ),
            passo('formulario', 'Notas por critério', 'Cada critério mostra o peso e a nota máxima. Dê a nota e, se quiser, um comentário.'),
            passo(
                'acao',
                'Enviar avaliação',
                'O botão libera quando todos os critérios têm nota. Depois de enviada, a nota não pode mais ser alterada por aqui.',
            ),
        ],
    },
    'admin/index': {
        id: 'painel.index',
        steps: [
            conteudo('Painel da organização', 'Visão geral da edição: o que precisa de atenção e o que acontece hoje.'),
            passo(
                'pendencias',
                'Precisa de atenção',
                'Equipes que ainda não enviaram projeto e avaliações em aberto. Clique em um item para resolver.',
            ),
            menu('/painel/evento', 'Evento', 'Datas, fases e configurações da edição.'),
            menu('/painel/checkin', 'Check-in', 'Registre a presença lendo o QR do crachá.'),
            menu('/painel/resultados', 'Resultados', 'Recalcule o ranking e publique o resultado.'),
        ],
    },
    'admin/evento/editar': {
        id: 'painel.evento',
        steps: [
            conteudo('Editar evento', 'Configuração da edição. O tema é o desafio que as equipes resolvem e aparece na página pública.'),
            passo(
                'formulario',
                'Dados da edição',
                'Datas, fase (rascunho não aparece em nenhuma página pública), tamanho das equipes e a assinatura e o visual do certificado. Os horários são os de Brasília.',
            ),
        ],
    },
    'admin/submissoes/index': {
        id: 'painel.submissoes',
        steps: [
            conteudo(
                'Submissões',
                'Todos os projetos registrados nesta edição. Daqui você também lança uma submissão manualmente e baixa tudo em .zip.',
            ),
            passo('resumo', 'Resumo por situação', 'Quantas submissões há em cada situação. Clique em uma para filtrar a lista.'),
            passo('filtros', 'Busca e filtros', 'Busque por equipe ou título e filtre por trilha.'),
            passo('tabela', 'Lista', 'Clique no nome da equipe para abrir a submissão, ver os detalhes e decidir sobre envios fora do prazo.'),
        ],
    },
    'admin/equipes/index': {
        id: 'painel.equipes',
        steps: [
            conteudo('Equipes', 'Todas as equipes desta edição, mesmo as que ainda não enviaram nada.'),
            passo('filtros', 'Busca', 'Busque pelo nome da equipe e filtre por situação.'),
            passo(
                'tabela',
                'Lista de equipes',
                'Desclassifique ou reabilite uma equipe pelo botão da linha. Você informa um motivo, que os integrantes recebem por e-mail.',
            ),
        ],
    },
    'admin/agenda/index': {
        id: 'painel.agenda',
        steps: [
            conteudo('Agenda', 'A programação do evento. Só o que estiver publicado aparece para o público.'),
            passo('acao', 'Novo item', 'Cadastre uma nova atividade da programação.'),
            passo('lista', 'Itens da agenda', 'Publique ou despublique, edite ou remova cada item. Rascunhos não aparecem no site público.'),
        ],
    },
    'admin/checkin/index': {
        id: 'painel.checkin',
        manual: true,
        steps: [
            conteudo('Check-in', 'Peça para a pessoa mostrar o crachá e escaneie com a câmera do celular. Sem QR, busque pelo nome.'),
            passo(
                'formulario',
                'Primeiro checkpoint',
                'Sem ao menos um checkpoint não dá para confirmar presença. Crie o primeiro, por exemplo "Entrada".',
            ),
            passo('leitor', 'Leitor de QR', 'Aponte a câmera para o QR do crachá. Se não funcionar, use a busca por nome logo abaixo.'),
            passo('busca', 'Busca por nome', 'Procure a pessoa pelo nome e confirme a presença no botão da linha.'),
        ],
    },
    'admin/incidentes/index': {
        id: 'painel.incidentes',
        steps: [
            conteudo('Incidentes', 'Uma extensão de prazo vale para todas as equipes, nunca só para quem avisou.'),
            passo(
                'formulario',
                'Declarar incidente',
                'Escolha o tipo, descreva o que aconteceu e informe a extensão em minutos (0 se não houver). Com extensão, o sistema pede confirmação.',
            ),
            passo('lista', 'Histórico', 'Cada incidente mostra quem declarou, quando e qual foi a extensão.'),
        ],
    },
    'admin/rubrica/index': {
        id: 'painel.rubrica',
        steps: [
            conteudo('Rubrica', 'Só a rubrica ativa conta para o cálculo e aparece para os jurados e para o público.'),
            passo('formulario', 'Nova rubrica', 'Crie uma rubrica pelo nome. Depois, abra-a para montar os critérios e seus pesos.'),
            passo(
                'lista',
                'Rubricas',
                'Ative a rubrica que vale. Cada uma mostra o número de critérios e a soma dos pesos. Excluir apaga também os critérios e não pode ser desfeito.',
            ),
        ],
    },
    'admin/jurados/index': {
        id: 'painel.jurados',
        steps: [
            conteudo(
                'Jurados',
                'Distribua as submissões entre os jurados. A distribuição é uma sugestão: ajustes feitos à mão não são sobrescritos por uma nova rodada.',
            ),
            passo(
                'acao',
                'Distribuir automaticamente',
                'Atribui as submissões aos jurados, respeitando o número de jurados por submissão definido logo abaixo.',
            ),
            passo('formulario', 'Atribuir manualmente', 'Escolha uma submissão e um jurado para ajustar um caso específico.'),
            passo(
                'lista',
                'Submissões e jurados',
                'Veja quem avalia cada submissão. Avaliação enviada pode ser reaberta para correção (com motivo); a pendente pode ser reatribuída ou removida.',
            ),
        ],
    },
    'admin/resultados/index': {
        id: 'painel.resultados',
        steps: [
            conteudo('Resultados', 'Mostra se o resultado já foi publicado e quando foi o último cálculo.'),
            passo('recalcular', 'Recalcular', 'Refaz o ranking com as notas atuais.'),
            passo(
                'acao',
                'Publicar resultado',
                'Torna o resultado público. Se houver pendências (submissões sem nota, avaliações incompletas ou empates), o sistema pede confirmação antes.',
            ),
            passo('tabela', 'Ranking', 'Posição geral, posição na trilha e nota final de cada submissão.'),
        ],
    },
    'admin/certificados/index': {
        id: 'painel.certificados',
        steps: [
            conteudo(
                'Certificados',
                'Os certificados de participação, jurado, organizador e colocação saem em lote por comando. Aqui é só a emissão avulsa, como mentoria ou correção pontual.',
            ),
            passo(
                'formulario',
                'Emitir certificado avulso',
                'Escolha a pessoa e o tipo. Para colocação, informe também o texto, como "1º lugar geral".',
            ),
            passo(
                'tabela',
                'Emitidos',
                'Mostra se o PDF de cada certificado já está pronto. Qualquer certificado pode ser conferido publicamente em /validar/código.',
            ),
        ],
    },
    'admin/usuarios/index': {
        id: 'admin.usuarios',
        steps: [
            conteudo('Usuários', 'Conceda ou remova papéis de jurado, organizador e admin (somente administradores).'),
            passo('filtros', 'Busca', 'Busque por nome ou e-mail.'),
            passo(
                'tabela',
                'Papéis',
                'Use "Editar papéis" na linha da pessoa. Jurado, organizador e admin acumulam entre si, mas marcar um deles remove o papel de participante. Você não pode remover o seu próprio papel de admin.',
            ),
        ],
    },
};
