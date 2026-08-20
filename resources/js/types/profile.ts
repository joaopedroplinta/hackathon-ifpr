export type TipoVinculo = 'aluno_ifpr' | 'professor_ifpr' | 'externo';

export type IdentidadeInstitucional = {
    cpf: string | null;
    tipo_vinculo: TipoVinculo | null;
    matricula_suap: string | null;
    matricula_siape: string | null;
};
