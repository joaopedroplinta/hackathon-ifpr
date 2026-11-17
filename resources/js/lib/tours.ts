/**
 * Catálogo dos tutoriais guiados, um por página interna (chave = nome do
 * componente Inertia). Cada tela só é alcançável pelo papel certo (Policy),
 * então o papel não precisa ser repetido aqui.
 *
 * `target` é o valor de `data-tour` no elemento. Itens do menu lateral usam
 * `nav:<url>`. `titulo` é o `h1` da página (sem atributo: toda página tem um). Alvo ausente na tela (menu que o papel não vê, lista vazia,
 * menu recolhido no celular) faz o passo ser pulado -- nunca quebra.
 *
 * `id` é estável e é o que fica salvo em `users.tours_seen`. Mudou o
 * conteúdo de forma relevante? Sufixe o id (`.v2`) para o tour reaparecer.
 * Formato aceito pelo servidor: /^[a-z0-9.\-]{1,64}$/.
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
            conteudo('Bem-vindo ao Hackathon IFPR', 'Esta é a sua página inicial. Ela mostra o que falta fazer e leva você direto ao próximo passo.'),
            menu('/equipe', 'Minha equipe', 'Crie uma equipe ou entre em uma com o código de convite.'),
            menu('/submissao', 'Meu projeto', 'Envie a descrição e os arquivos do projeto antes do prazo.'),
            menu('/credencial', 'Crachá', 'Seu QR Code para o check-in no dia do evento.'),
            menu('/jurado', 'Avaliar', 'Os projetos atribuídos a você ficam aqui.'),
            menu('/painel', 'Painel de organização', 'Pendências, equipes, agenda, check-in e resultados da edição.'),
            menu('/certificados', 'Certificados', 'Seus certificados aparecem aqui depois do evento.'),
        ],
    },
    'equipe/minha': {
        id: 'equipe.minha',
        steps: [
            conteudo('Sua equipe', 'Aqui você vê os integrantes e o status da equipe.'),
            passo('convite', 'Código de convite', 'Compartilhe este código para chamar colegas para a equipe.'),
            passo('equipe', 'Integrantes', 'Quem lidera a equipe também gerencia os integrantes.'),
            menu('/submissao', 'Próximo passo', 'Com a equipe formada, envie o projeto em "Meu projeto".'),
        ],
    },
    'equipe/sem-equipe': {
        id: 'equipe.sem-equipe',
        steps: [
            conteudo('Você ainda não tem equipe', 'Para participar, crie uma equipe nova ou entre em uma existente.'),
            passo('acao', 'Criar ou entrar', 'Crie a equipe e convide colegas, ou use o código de convite que recebeu.'),
        ],
    },
    'submissao/minha': {
        id: 'submissao.minha',
        steps: [
            conteudo('Meu projeto', 'Descreva o projeto e envie os arquivos. O prazo é conferido pelo servidor, não pelo relógio da tela.'),
            passo(
                'formulario',
                'Formulário do projeto',
                'Preencha os campos e salve como rascunho quantas vezes quiser. Só o envio final conta para a avaliação.',
            ),
        ],
    },
    'credencial/mostrar': {
        id: 'credencial.mostrar',
        steps: [conteudo('Seu crachá digital', 'Mostre este QR Code na entrada e nos pontos de check-in. Ele é pessoal: não compartilhe a imagem.')],
    },
    'certificados/index': {
        id: 'certificados.index',
        steps: [
            conteudo('Certificados', 'Os certificados emitidos para você aparecem aqui.'),
            passo('lista', 'Seus certificados', 'Baixe o PDF. O código impresso permite que qualquer pessoa valide o documento.'),
        ],
    },
    'jurado/fila': {
        id: 'jurado.fila',
        steps: [
            conteudo('Fila de avaliação', 'Aqui ficam só os projetos atribuídos a você.'),
            passo('lista', 'Projetos atribuídos', 'Abra um projeto para avaliar. Você pode voltar e continuar depois, até enviar a nota.'),
        ],
    },
    'jurado/avaliar': {
        id: 'jurado.avaliar',
        manual: true,
        steps: [
            conteudo('Avaliar projeto', 'Leia o projeto e dê uma nota para cada critério da rubrica.'),
            passo('resumo', 'Resumo do projeto', 'Descrição e arquivos enviados pela equipe.'),
            passo('formulario', 'Notas por critério', 'Preencha todos os critérios. A nota final é calculada com os pesos da rubrica.'),
            passo(
                'acao',
                'Enviar avaliação',
                'O botão libera quando todos os critérios estão preenchidos. Depois de enviada, só a organização reabre.',
            ),
        ],
    },
    'admin/index': {
        id: 'painel.index',
        steps: [
            conteudo('Painel da organização', 'Visão geral da edição: o que precisa de atenção e o que acontece hoje.'),
            passo('pendencias', 'Pendências', 'Itens que pedem uma decisão sua, como submissões e incidentes.'),
            menu('/painel/evento', 'Evento', 'Datas, prazos e dados da edição.'),
            menu('/painel/checkin', 'Check-in', 'Leia o QR do crachá no dia do evento.'),
            menu('/painel/resultados', 'Resultados', 'Recalcule o ranking e publique o resultado.'),
        ],
    },
    'admin/evento/editar': {
        id: 'painel.evento',
        steps: [
            conteudo('Dados do evento', 'Configure a edição atual.'),
            passo(
                'formulario',
                'Datas e prazos',
                'Os prazos valem pelo horário do servidor (Brasília). Mudar o prazo de todas as equipes é feito em Incidentes.',
            ),
        ],
    },
    'admin/submissoes/index': {
        id: 'painel.submissoes',
        steps: [
            conteudo('Submissões', 'Todos os projetos enviados pelas equipes.'),
            passo('resumo', 'Resumo', 'Quantas submissões há em cada situação.'),
            passo('filtros', 'Filtros', 'Filtre a lista para achar uma submissão.'),
            passo('tabela', 'Lista', 'Abra uma submissão para ver detalhes, arquivos e decidir sobre atrasos.'),
        ],
    },
    'admin/equipes/index': {
        id: 'painel.equipes',
        steps: [
            conteudo('Equipes', 'Todas as equipes do evento.'),
            passo('filtros', 'Busca', 'Encontre uma equipe pela busca.'),
            passo('tabela', 'Lista de equipes', 'Aqui você desclassifica ou reintegra uma equipe. A ação fica registrada com autor e motivo.'),
        ],
    },
    'admin/agenda/index': {
        id: 'painel.agenda',
        steps: [
            conteudo('Agenda', 'A programação que aparece no site público.'),
            passo('acao', 'Nova atividade', 'Cadastre uma nova atividade da programação.'),
            passo('lista', 'Atividades', 'Edite ou remova atividades. A exibição usa o horário de Brasília.'),
        ],
    },
    'admin/checkin/index': {
        id: 'painel.checkin',
        manual: true,
        steps: [
            conteudo('Check-in', 'Registre a presença no dia do evento, inclusive pelo celular.'),
            passo('formulario', 'Pontos de check-in', 'Crie os pontos de check-in do evento, como entrada e encerramento.'),
            passo('leitor', 'Leitor de QR', 'Aponte a câmera para o QR do crachá. Se a câmera não funcionar, use a busca abaixo.'),
        ],
    },
    'admin/incidentes/index': {
        id: 'painel.incidentes',
        steps: [
            conteudo('Incidentes', 'Registre problemas que afetam todas as equipes, como queda de internet.'),
            passo('formulario', 'Declarar incidente', 'Informe o motivo e a extensão do prazo. Vale para todas as equipes e fica registrado.'),
            passo('lista', 'Histórico', 'Incidentes já declarados.'),
        ],
    },
    'admin/rubrica/index': {
        id: 'painel.rubrica',
        steps: [
            conteudo('Rubricas', 'Os critérios e pesos usados pelos jurados.'),
            passo('formulario', 'Nova rubrica', 'Crie uma rubrica e depois adicione os critérios com seus pesos.'),
            passo('lista', 'Rubricas', 'Só uma fica ativa. Evite trocar a rubrica depois que as avaliações começam.'),
        ],
    },
    'admin/jurados/index': {
        id: 'painel.jurados',
        steps: [
            conteudo('Jurados', 'Distribua os projetos entre os jurados.'),
            passo('acao', 'Distribuir', 'Atribui os projetos aos jurados conforme a configuração abaixo.'),
            passo('formulario', 'Atribuição manual', 'Use para ajustar um caso específico.'),
            passo('lista', 'Andamento', 'Quem já avaliou o quê. Reabra uma avaliação quando necessário.'),
        ],
    },
    'admin/resultados/index': {
        id: 'painel.resultados',
        steps: [
            conteudo('Resultados', 'Ranking calculado a partir das avaliações.'),
            passo('recalcular', 'Recalcular', 'Refaz o ranking com as notas atuais. Pode repetir sem risco.'),
            passo('acao', 'Publicar', 'Torna o resultado público. A publicação fica registrada.'),
            passo('tabela', 'Ranking', 'Posição e nota final de cada equipe.'),
        ],
    },
    'admin/certificados/index': {
        id: 'painel.certificados',
        steps: [
            conteudo('Emitir certificados', 'Emissão de certificados.'),
            passo('formulario', 'Certificado avulso', 'Emita para uma pessoa específica, por exemplo um palestrante.'),
            passo('tabela', 'Emitidos', 'Certificados já emitidos.'),
        ],
    },
    'admin/usuarios/index': {
        id: 'admin.usuarios',
        steps: [
            conteudo('Usuários', 'Gerencie os papéis de cada pessoa (somente administrador).'),
            passo('filtros', 'Busca', 'Encontre uma pessoa pela busca.'),
            passo('tabela', 'Papéis', 'Altere o papel com cuidado: jurado e organizador não podem ser participantes.'),
        ],
    },
};
