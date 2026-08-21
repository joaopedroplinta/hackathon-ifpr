// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type LinhaUsuario = {
    id: number;
    nome: string;
    email: string;
    papeis: string[];
    /** Some o checkbox de admin pra si mesmo -- ver UpdateUserRoles. */
    sou_eu: boolean;
};

export type FiltrosUsuario = {
    busca: string | null;
};

export type OpcaoPapel = {
    value: string;
    label: string;
};
