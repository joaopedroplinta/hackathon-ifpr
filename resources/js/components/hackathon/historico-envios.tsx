import { History } from 'lucide-react';

import { VersaoEnvio } from '@/types/submissao';

type Props = {
    versoes: VersaoEnvio[];
};

function formatarData(iso: string): string {
    return new Date(iso).toLocaleString('pt-BR', {
        timeZone: 'America/Sao_Paulo',
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

/**
 * Cada envio vira uma versão, nunca sobrescreve a anterior -- a equipe
 * confere aqui quem mandou e quando, sem precisar perguntar no grupo.
 */
export default function HistoricoEnvios({ versoes }: Props) {
    return (
        <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 sm:p-6">
            <h2 className="flex items-center gap-2 font-medium">
                <History className="h-4 w-4 shrink-0" aria-hidden="true" />
                Histórico de envios
            </h2>

            {versoes.length === 0 ? (
                <p className="text-muted-foreground mt-2 text-sm">
                    Nenhum envio ainda. Preencham o formulário acima e cliquem em "Enviar projeto".
                </p>
            ) : (
                <ol className="mt-4 divide-y">
                    {versoes.map((versao) => (
                        <li key={versao.versao} className="flex items-center justify-between gap-3 py-3 text-sm">
                            <span className="font-medium">Versão {versao.versao}</span>
                            <span className="text-muted-foreground text-right">
                                {versao.autor} · {formatarData(versao.criado_em)}
                            </span>
                        </li>
                    ))}
                </ol>
            )}
        </section>
    );
}
