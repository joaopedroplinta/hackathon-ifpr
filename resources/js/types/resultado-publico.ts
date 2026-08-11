// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type LinhaPodio = {
    posicao: number;
    titulo: string;
    equipe: string;
    nota_final: number;
    trilha: string | null;
};

export type PremioPopular = {
    titulo: string;
    equipe: string;
    votos: number;
};
