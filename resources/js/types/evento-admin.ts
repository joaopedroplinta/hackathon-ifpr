// `type`, não `interface`: mesma razão de types/submissao.ts -- CLAUDE.md.

export type EventoExistente = {
    name: string;
    description: string | null;
    status: string;
    registration_opens_at: string | null;
    registration_closes_at: string | null;
    starts_at: string | null;
    ends_at: string | null;
    submission_deadline: string | null;
    voting_opens_at: string | null;
    voting_closes_at: string | null;
    min_team_size: number;
    max_team_size: number;
};

export type EventoForm = {
    name: string;
    description: string;
    status: string;
    registration_opens_at: string;
    registration_closes_at: string;
    starts_at: string;
    ends_at: string;
    submission_deadline: string;
    voting_opens_at: string;
    voting_closes_at: string;
    min_team_size: string;
    max_team_size: string;
};

export type NovoEventoForm = {
    name: string;
    description: string;
    registration_opens_at: string;
    registration_closes_at: string;
    starts_at: string;
    ends_at: string;
    submission_deadline: string;
    voting_opens_at: string;
    voting_closes_at: string;
    min_team_size: string;
    max_team_size: string;
};

export type OpcaoStatusEvento = { value: string; label: string };

export type RegulamentoEvento = { nome_arquivo: string | null; atualizado_em: string | null };
