// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type SubmissaoVitrine = {
    id: number;
    titulo: string;
    resumo: string | null;
    equipe: string;
};
