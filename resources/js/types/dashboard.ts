export type StatusPasso = 'concluido' | 'disponivel' | 'bloqueado';

export type PassoTrilha = {
    chave: string;
    titulo: string;
    descricao: string;
    status: StatusPasso;
    href: string | null;
};
