// `type`, não `interface`: mesma razão de types/submissao.ts -- CLAUDE.md.

import { StatusSubmissao } from '@/types/submissao';

export type LinhaSubmissao = {
    id: number;
    titulo: string | null;
    equipe: { nome: string; slug: string };
    trilha: { nome: string; cor: string | null } | null;
    status: StatusSubmissao;
    status_label: string;
    origem_label: string;
    /** Entrou por fora do sistema (formulário, e-mail, lançamento manual). */
    precisa_conferencia: boolean;
    /** ISO 8601, exibido em America/Sao_Paulo. */
    enviado_em: string | null;
    versao_atual: number;
    arquivos: number;
};

export type FiltrosSubmissao = {
    status: StatusSubmissao | null;
    track_id: number | null;
    busca: string | null;
};

export type OpcoesSubmissao = {
    status: { valor: StatusSubmissao; rotulo: string }[];
    trilhas: { id: number; nome: string }[];
};

export type ResumoSubmissoes = {
    total: number;
    por_status: { valor: StatusSubmissao; rotulo: string; total: number }[];
    /** Equipes ativas que ainda não enviaram. É o que se olha antes do prazo. */
    equipes_sem_envio: string[];
};

export type DetalheSubmissao = {
    id: number;
    titulo: string | null;
    resumo: string | null;
    descricao: string | null;
    repo_url: string | null;
    video_url: string | null;
    deploy_url: string | null;
    status: StatusSubmissao;
    status_label: string;
    origem_label: string;
    precisa_conferencia: boolean;
    enviado_em: string | null;
    versao_atual: number;
    equipe: { nome: string; slug: string };
    trilha: { nome: string; cor: string | null } | null;
};

/** Um envio completo. `payload` é o retrato gravado no momento do envio. */
export type VersaoSubmissao = {
    versao: number;
    autor: string;
    /** ISO 8601, exibido em America/Sao_Paulo. */
    criado_em: string;
    payload: Record<string, unknown>;
};

export type ArquivoDetalhe = {
    id: number;
    nome: string;
    tamanho: string;
    versao: number;
};
