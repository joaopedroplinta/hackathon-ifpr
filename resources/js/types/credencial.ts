// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type Credencial = {
    nome: string;
    /** Markup SVG pronto, gerado no servidor -- o React só injeta. */
    qr_svg: string;
    token: string;
};
