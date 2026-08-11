// `type`, não `interface` -- mesma razão de types/submissao.ts (CLAUDE.md).

export type CheckpointOpcao = {
    id: number;
    nome: string;
    tipo_label: string;
};

export type ResultadoBusca = {
    id: number;
    nome: string;
    email: string;
    confirmar_href: string;
};

export type ParticipanteCheckin = {
    id: number;
    nome: string;
    email: string;
    avatar_url: string | null;
};
