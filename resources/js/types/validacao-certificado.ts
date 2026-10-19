export type ValidacaoCertificado =
    | { encontrado: false; revogado?: boolean }
    | {
          encontrado: true;
          nome: string;
          tipo_label: string;
          evento: string;
          carga_horaria: number;
          emitido_em: string;
      };
