// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type JuradoAtribuido = {
    atribuicao_id: number;
    jurado_id: number;
    nome: string;
    status_label: string;
};

export type LinhaSubmissaoJurados = {
    id: number;
    titulo: string;
    equipe: string;
    jurados: JuradoAtribuido[];
};

export type LinhaJurado = {
    id: number;
    nome: string;
    total_atribuicoes: number;
};

export type LinhaConflito = {
    id: number;
    jurado: string;
    equipe: string;
    motivo: string | null;
};
