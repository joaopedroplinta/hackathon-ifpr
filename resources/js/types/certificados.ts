export type LinhaCertificado = {
    id: number;
    nome: string;
    tipo: string;
    tipo_label: string;
    pronto: boolean;
    emitido_em: string;
    codigo: string;
};

export type TipoCertificado = {
    value: string;
    label: string;
};
