// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type TipoItemAgenda = 'palestra' | 'workshop' | 'checkpoint' | 'refeicao' | 'deadline';

export type ItemAgenda = {
    id: number;
    titulo: string;
    descricao: string | null;
    tipo: TipoItemAgenda;
    tipo_label: string;
    /** Checkpoint ou prazo -- ganha destaque na timeline. */
    destaque: boolean;
    /** ISO 8601, exibido em America/Sao_Paulo. */
    inicia_em: string;
    termina_em: string;
    local: string | null;
    palestrante: string | null;
    trilha: { nome: string; cor: string | null } | null;
};

export type EventoPublico = {
    nome: string;
    descricao: string | null;
    edicao: number;
    situacao: 'draft' | 'published' | 'running' | 'finished';
    situacao_label: string;
    inicia_em: string | null;
    termina_em: string | null;
    inscricoes_abrem_em: string | null;
    inscricoes_fecham_em: string | null;
    inscricoes_abertas: boolean;
};
