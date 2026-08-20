import { Head, Link } from '@inertiajs/react';
import { History, Trophy } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Edicao } from '@/types/edicao';

interface Props {
    edicoes: Edicao[];
}

function formatarData(iso: string | null): string | null {
    if (!iso) return null;

    return new Date(iso).toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo', day: '2-digit', month: 'long', year: 'numeric' });
}

export default function Edicoes({ edicoes }: Props) {
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Edições anteriores" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="font-display text-2xl font-semibold tracking-tight">Edições anteriores</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Resultado publicado de hackathons já encerrados.</p>
                </header>

                {edicoes.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border border-dashed p-10 text-center">
                        <History className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Nenhuma edição encerrada ainda</p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Quando uma edição terminar e o resultado for publicado, ela aparece aqui.
                        </p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {edicoes.map((edicao) => {
                            const dataEncerramento = formatarData(edicao.encerrado_em);

                            return (
                                <li key={edicao.slug}>
                                    <Link
                                        href={route('resultados.show.edicao', edicao.slug)}
                                        className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-accent flex items-center gap-3 rounded-xl border p-4"
                                    >
                                        <Trophy className="text-muted-foreground h-5 w-5 shrink-0" aria-hidden="true" />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium">{edicao.nome}</p>
                                            <p className="text-muted-foreground text-xs">
                                                Edição {edicao.edicao}
                                                {dataEncerramento && ` · encerrada em ${dataEncerramento}`}
                                            </p>
                                        </div>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
