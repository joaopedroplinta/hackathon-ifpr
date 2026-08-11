// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type Criterio = {
    id: number;
    nome: string;
    descricao: string | null;
    peso: number;
    nota_maxima: number;
};

export type LinhaRubrica = {
    id: number;
    nome: string;
    ativa: boolean;
    total_criterios: number;
    soma_pesos: number;
};

export type CriterioForm = {
    name: string;
    description: string;
    weight: string;
    max_score: string;
};
