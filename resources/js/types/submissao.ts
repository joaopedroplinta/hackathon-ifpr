// `type`, não `interface`: o useForm do Inertia v2 exige `T extends
// FormDataType` e só o `type` ganha index signature implícita -- CLAUDE.md.

export type StatusSubmissao = 'draft' | 'submitted' | 'late' | 'disqualified';

export type Submissao = {
    title: string | null;
    summary: string | null;
    description: string | null;
    repo_url: string | null;
    video_url: string | null;
    deploy_url: string | null;
    status: StatusSubmissao;
    status_label: string;
    enviado_em: string | null;
    versao_atual: number;
    foi_enviada: boolean;
    fora_do_prazo: boolean;
};

export type PrazoSubmissao = {
    /** ISO 8601. Só para exibição -- quem decide o prazo é o servidor. */
    encerra_em: string | null;
    aberto: boolean;
};

/** Os campos que o formulário manda. Espelha as duas Form Requests. */
export type SubmissaoForm = {
    title: string;
    summary: string;
    description: string;
    repo_url: string;
    video_url: string;
    deploy_url: string;
};
