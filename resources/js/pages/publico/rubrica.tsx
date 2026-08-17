import { Head } from '@inertiajs/react';
import { ClipboardList, Scale } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Criterio } from '@/types/rubrica';

interface Props {
    evento: { nome: string } | null;
    criterios: Criterio[];
}

export default function Rubrica({ evento, criterios }: Props) {
    const somaPesos = criterios.reduce((soma, c) => soma + c.peso, 0);

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Rubrica" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="font-display text-2xl font-semibold tracking-tight">Rubrica de avaliação</h1>
                    {evento && <p className="text-muted-foreground mt-1 text-sm">{evento.nome}</p>}
                    <p className="text-muted-foreground mt-2 text-sm">
                        Cada jurado avalia com estes critérios. A nota da avaliação é a média ponderada pelos pesos abaixo.
                    </p>
                </header>

                {criterios.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <ClipboardList className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Rubrica ainda não publicada</p>
                        <p className="text-muted-foreground mt-1 text-sm">A organização ainda está definindo os critérios de avaliação.</p>
                    </div>
                ) : (
                    <>
                        <ol className="flex flex-col gap-3">
                            {criterios.map((criterio, indice) => (
                                <li key={criterio.id} className="rounded-xl border p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <p className="font-medium">
                                            {indice + 1}. {criterio.nome}
                                        </p>
                                        <span className="text-muted-foreground flex items-center gap-1 font-mono text-xs">
                                            <Scale className="h-3 w-3 shrink-0" aria-hidden="true" />
                                            peso {criterio.peso} · nota até {criterio.nota_maxima}
                                        </span>
                                    </div>
                                    {criterio.descricao && <p className="text-muted-foreground mt-1 text-sm">{criterio.descricao}</p>}
                                </li>
                            ))}
                        </ol>

                        <p className="text-muted-foreground text-xs">Soma dos pesos: {somaPesos}.</p>
                    </>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
