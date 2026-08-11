// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

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
