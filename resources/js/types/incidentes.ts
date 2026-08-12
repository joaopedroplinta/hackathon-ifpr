export type LinhaIncidente = {
    id: number;
    tipo_label: string;
    descricao: string;
    extensao_minutos: number;
    declarado_por: string;
    declarado_em: string;
};

export type TipoIncidente = { value: string; label: string };
