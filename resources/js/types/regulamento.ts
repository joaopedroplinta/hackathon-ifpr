// `type`, não `interface` -- mesma razão de types/evento-admin.ts (CLAUDE.md).

export type EventoRegulamento = {
    nome: string;
    min_team_size: number;
    max_team_size: number;
    submission_deadline: string | null;
} | null;

export type ArquivoRegulamento = {
    tem_arquivo: boolean;
    nome_arquivo: string | null;
    atualizado_em: string | null;
};
