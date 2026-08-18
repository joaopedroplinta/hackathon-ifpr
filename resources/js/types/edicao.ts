// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type Edicao = {
    nome: string;
    edicao: number;
    slug: string;
    encerrado_em: string | null;
};
