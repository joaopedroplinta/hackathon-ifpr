import { Tag } from 'lucide-react';

import EstadoVazio from '@/components/hackathon/estado-vazio';
import Status from '@/components/hackathon/status';
import PublicLayout from '@/layouts/public-layout';
import { type Versao } from '@/types/publico';

// Data pura (AAAA-MM-DD): UTC, senão o fuso de São Paulo recua um dia.
const formatarData = (data: string) =>
    new Date(data).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });

type Props = { versoes: Versao[]; atual: string };

export default function Versoes({ versoes, atual }: Props) {
    return (
        <PublicLayout titulo="Versões" descricao="O que mudou no sistema a cada atualização.">
            {versoes.length === 0 ? (
                <EstadoVazio icon={Tag} titulo="Nenhuma versão publicada ainda" descricao="Quando houver uma atualização, ela aparece aqui." />
            ) : (
                <ol className="space-y-5">
                    {versoes.map((versao) => (
                        <li key={versao.numero} className="min-w-0">
                            <section id={`v${versao.numero}`} className="border-border bg-card scroll-mt-24 rounded-2xl border p-5 sm:p-8">
                                <div className="flex flex-wrap items-center gap-3">
                                    <h2 className="font-mono text-2xl font-semibold tracking-tight">v{versao.numero}</h2>
                                    {versao.numero === atual && <Status tom="sucesso">Versão atual</Status>}
                                    <time dateTime={versao.data} className="text-muted-foreground text-sm sm:ml-auto">
                                        {formatarData(versao.data)}
                                    </time>
                                </div>

                                {versao.resumo && (
                                    <p
                                        className="text-muted-foreground mt-4 text-sm break-words [&_code]:font-mono"
                                        dangerouslySetInnerHTML={{ __html: versao.resumo }}
                                    />
                                )}

                                {versao.secoes.map((secao) => (
                                    <div key={secao.titulo} className="mt-6">
                                        <h3 className="text-sm font-semibold">{secao.titulo}</h3>
                                        <ul className="mt-2 list-disc space-y-2 pl-5 text-sm">
                                            {secao.itens.map((html, i) => (
                                                <li
                                                    key={i}
                                                    className="break-words [&_code]:font-mono [&_code]:text-xs"
                                                    dangerouslySetInnerHTML={{ __html: html }}
                                                />
                                            ))}
                                        </ul>
                                    </div>
                                ))}
                            </section>
                        </li>
                    ))}
                </ol>
            )}
        </PublicLayout>
    );
}
