// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type LinhaResultado = {
    submission_id: number;
    titulo: string;
    equipe: string;
    trilha: string | null;
    nota_final: number | null;
    rank_overall: number | null;
    rank_track: number | null;
};

export type JuradoIncompleto = {
    titulo: string;
    enviadas: number;
    total: number;
};

export type Empate = {
    posicao: number;
    submissoes: string[];
};

export type Pendencias = {
    /** Envios fora do prazo que ninguém aceitou nem recusou: ficam fora do ranking. */
    atrasados_sem_decisao: string[];
    submissoes_sem_nota: string[];
    jurados_incompletos: JuradoIncompleto[];
    empates: Empate[];
};
