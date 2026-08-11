import { Head } from '@inertiajs/react';
import { Medal, Trophy, Users } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import { LinhaPodio, PremioPopular } from '@/types/resultado-publico';

interface Props {
    publicado: boolean;
    evento: { nome: string } | null;
    podio_geral: LinhaPodio[];
    podio_por_trilha: Record<string, LinhaPodio[]>;
    premio_popular: PremioPopular | null;
}

const corDaPosicao: Record<number, string> = {
    1: 'text-amber-500 dark:text-amber-400',
    2: 'text-slate-400 dark:text-slate-300',
    3: 'text-amber-700 dark:text-amber-600',
};

function ListaPodio({ linhas }: { linhas: LinhaPodio[] }) {
    return (
        <ol className="flex flex-col gap-3">
            {linhas.map((linha) => (
                <li
                    key={`${linha.posicao}-${linha.titulo}`}
                    className="border-sidebar-border/70 dark:border-sidebar-border flex items-center gap-3 rounded-xl border p-4"
                >
                    <span className={`flex w-8 shrink-0 items-center justify-center text-lg font-bold ${corDaPosicao[linha.posicao] ?? ''}`}>
                        {linha.posicao <= 3 ? <Medal className="h-6 w-6" aria-hidden="true" /> : linha.posicao}
                    </span>
                    <div className="min-w-0 flex-1">
                        <p className="truncate font-medium">{linha.titulo}</p>
                        <p className="text-muted-foreground truncate text-xs">
                            {linha.equipe}
                            {linha.trilha && ` · ${linha.trilha}`}
                        </p>
                    </div>
                    <span className="shrink-0 text-sm font-medium">{linha.nota_final.toFixed(2)}</span>
                </li>
            ))}
        </ol>
    );
}

export default function Resultados({ publicado, evento, podio_geral, podio_por_trilha, premio_popular }: Props) {
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Resultados" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="text-2xl font-semibold">Resultados</h1>
                    {evento && <p className="text-muted-foreground mt-1 text-sm">{evento.nome}</p>}
                </header>

                {!publicado ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <Trophy className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Resultado ainda não publicado</p>
                        <p className="text-muted-foreground mt-1 text-sm">A organização está conferindo as notas antes de divulgar a colocação.</p>
                    </div>
                ) : (
                    <>
                        <section>
                            <h2 className="mb-3 flex items-center gap-2 font-medium">
                                <Trophy className="h-4 w-4 shrink-0" aria-hidden="true" />
                                Pódio geral
                            </h2>
                            {podio_geral.length === 0 ? (
                                <p className="text-muted-foreground text-sm">Nenhuma submissão pontuada ainda.</p>
                            ) : (
                                <ListaPodio linhas={podio_geral} />
                            )}
                        </section>

                        {Object.keys(podio_por_trilha).length > 0 && (
                            <section>
                                <h2 className="mb-3 font-medium">Pódio por trilha</h2>
                                <div className="flex flex-col gap-6">
                                    {Object.entries(podio_por_trilha).map(([trilha, linhas]) => (
                                        <div key={trilha}>
                                            <p className="text-muted-foreground mb-2 text-sm font-medium">{trilha}</p>
                                            <ListaPodio linhas={linhas} />
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {premio_popular && (
                            <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 sm:p-6">
                                <h2 className="flex items-center gap-2 font-medium">
                                    <Users className="h-4 w-4 shrink-0" aria-hidden="true" />
                                    Prêmio popular
                                </h2>
                                <p className="mt-2 font-medium">{premio_popular.titulo}</p>
                                <p className="text-muted-foreground text-sm">
                                    {premio_popular.equipe} · {premio_popular.votos} {premio_popular.votos === 1 ? 'voto' : 'votos'}
                                </p>
                            </section>
                        )}
                    </>
                )}
            </main>
        </div>
    );
}
