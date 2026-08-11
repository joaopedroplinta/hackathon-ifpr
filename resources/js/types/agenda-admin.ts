// `type`, não `interface`: mesma razão de types/submissao.ts -- CLAUDE.md.

import { TipoItemAgenda } from '@/types/publico';

export type LinhaItemAgenda = {
    id: number;
    titulo: string;
    tipo_label: string;
    /** ISO 8601, exibido em America/Sao_Paulo. */
    inicia_em: string;
    termina_em: string;
    local: string | null;
    trilha: { nome: string; cor: string | null } | null;
    publicado: boolean;
};

/** Os campos que o formulário de criar/editar manda. */
export type ItemAgendaForm = {
    title: string;
    description: string;
    type: TipoItemAgenda | '';
    starts_at: string;
    ends_at: string;
    location: string;
    speaker_name: string;
    speaker_bio: string;
    track_id: string;
};

/** O item já salvo, como a edição recebe do servidor. */
export type ItemAgendaExistente = {
    id: number;
    title: string;
    description: string | null;
    type: TipoItemAgenda;
    starts_at: string;
    ends_at: string;
    location: string | null;
    speaker_name: string | null;
    speaker_bio: string | null;
    track_id: number | null;
};

export type OpcoesFormularioAgenda = {
    tipos: { valor: TipoItemAgenda; rotulo: string }[];
    trilhas: { id: number; nome: string }[];
};
