// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type SubmissaoFila = {
    submission_id: number;
    titulo: string;
    equipe: string;
    enviada: boolean;
};

export type Progresso = {
    avaliadas: number;
    total: number;
};

export type Criterio = {
    id: number;
    nome: string;
    descricao: string | null;
    peso: number;
    nota_maxima: number;
};

export type NotaCriterio = {
    criterion_id: number;
    score: number | null;
    comment: string | null;
};

export type SubmissaoAvaliar = {
    id: number;
    titulo: string;
    equipe: string;
    resumo: string | null;
    descricao: string | null;
    repo_url: string | null;
    video_url: string | null;
    deploy_url: string | null;
};
