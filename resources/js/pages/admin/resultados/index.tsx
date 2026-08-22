import { Head, router } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { AlertTriangle, LoaderCircle, RefreshCw, Send, Trophy } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { LinhaResultado, Pendencias } from '@/types/resultados';

interface Props {
    resultados: LinhaResultado[];
    pendencias: Pendencias;
    publicado_em: string | null;
    computado_em: string | null;
}

export default function ResultadosIndex({ resultados, pendencias, publicado_em, computado_em }: Props) {
    const [recalculando, setRecalculando] = useState(false);
    const [publicando, setPublicando] = useState(false);
    const [confirmandoComPendencia, setConfirmandoComPendencia] = useState(false);

    const temPendencia = pendencias.submissoes_sem_nota.length > 0 || pendencias.jurados_incompletos.length > 0 || pendencias.empates.length > 0;

    const recalcular = () => {
        setRecalculando(true);
        router.post(route('painel.resultados.recompute'), {}, { preserveScroll: true, onFinish: () => setRecalculando(false) });
    };

    const publicar = (confirmarPendencias: boolean) => {
        setPublicando(true);
        router.post(
            route('painel.resultados.publish'),
            { confirmar_pendencias: confirmarPendencias },
            { preserveScroll: true, onFinish: () => setPublicando(false), onSuccess: () => setConfirmandoComPendencia(false) },
        );
    };

    const clicarPublicar = () => {
        if (temPendencia) {
            setConfirmandoComPendencia(true);
            return;
        }

        publicar(false);
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Resultados', href: route('painel.resultados.index') }]}>
            <Head title="Resultados" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-4xl p-4 sm:p-6">
                <header className="mb-6 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Resultados</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {publicado_em ? `Publicado em ${publicado_em}.` : 'Ainda não publicado.'}
                            {computado_em && ` Último cálculo: ${computado_em}.`}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" onClick={recalcular} disabled={recalculando}>
                            {recalculando ? (
                                <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                            ) : (
                                <RefreshCw className="h-4 w-4" aria-hidden="true" />
                            )}
                            Recalcular
                        </Button>
                        <Button onClick={clicarPublicar} disabled={publicando}>
                            {publicando ? (
                                <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                            ) : (
                                <Send className="h-4 w-4" aria-hidden="true" />
                            )}
                            Publicar resultado
                        </Button>
                    </div>
                </header>

                {confirmandoComPendencia && (
                    <div className="mb-6 rounded-xl border border-amber-600/40 bg-amber-600/10 p-4">
                        <p className="flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400">
                            <AlertTriangle className="h-4 w-4 shrink-0" aria-hidden="true" />
                            Há pendências na lista abaixo. Publicar mesmo assim?
                        </p>
                        <div className="mt-3 flex gap-2">
                            <Button size="sm" onClick={() => publicar(true)} disabled={publicando}>
                                {publicando && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                Confirmar publicação mesmo com pendências
                            </Button>
                            <Button size="sm" variant="ghost" onClick={() => setConfirmandoComPendencia(false)}>
                                Cancelar
                            </Button>
                        </div>
                    </div>
                )}

                {temPendencia && (
                    <section className="border-border bg-card mb-6 rounded-xl border p-4 sm:p-6">
                        <h2 className="flex items-center gap-2 font-semibold">
                            <AlertTriangle className="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" aria-hidden="true" />
                            Pendências
                        </h2>

                        {pendencias.submissoes_sem_nota.length > 0 && (
                            <div className="mt-3">
                                <p className="text-sm font-medium">Submissões sem nota</p>
                                <ul className="text-muted-foreground mt-1 list-inside list-disc text-sm">
                                    {pendencias.submissoes_sem_nota.map((titulo) => (
                                        <li key={titulo}>{titulo}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {pendencias.jurados_incompletos.length > 0 && (
                            <div className="mt-3">
                                <p className="text-sm font-medium">Jurados incompletos</p>
                                <ul className="text-muted-foreground mt-1 list-inside list-disc text-sm">
                                    {pendencias.jurados_incompletos.map((item) => (
                                        <li key={item.titulo}>
                                            {item.titulo} — {item.enviadas} de {item.total} avaliações enviadas
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {pendencias.empates.length > 0 && (
                            <div className="mt-3">
                                <p className="text-sm font-medium">Empates pendentes</p>
                                <ul className="text-muted-foreground mt-1 list-inside list-disc text-sm">
                                    {pendencias.empates.map((empate) => (
                                        <li key={empate.posicao}>
                                            {empate.posicao}º lugar: {empate.submissoes.join(', ')}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </section>
                )}

                <h2 className="mb-3 font-semibold">Ranking</h2>
                {resultados.length === 0 ? (
                    <div className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Trophy className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhum resultado calculado ainda.</p>
                        <p className="text-muted-foreground text-sm">Clique em &quot;Recalcular&quot; para gerar o ranking.</p>
                    </div>
                ) : (
                    <div className="border-border bg-card overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[36rem] text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-border border-b text-left">
                                    <th className="px-4 py-3 text-xs font-semibold tracking-wide uppercase">Geral</th>
                                    <th className="px-4 py-3 text-xs font-semibold tracking-wide uppercase">Trilha</th>
                                    <th className="px-4 py-3 text-xs font-semibold tracking-wide uppercase">Submissão</th>
                                    <th className="px-4 py-3 text-xs font-semibold tracking-wide uppercase">Equipe</th>
                                    <th className="px-4 py-3 text-xs font-semibold tracking-wide uppercase">Nota</th>
                                </tr>
                            </thead>
                            <tbody>
                                {resultados.map((r) => (
                                    <tr key={r.submission_id} className="border-border border-b last:border-0">
                                        <td className="px-4 py-3">{r.rank_overall ?? '—'}</td>
                                        <td className="px-4 py-3">
                                            {r.rank_track ?? '—'}
                                            {r.trilha && <span className="text-muted-foreground"> ({r.trilha})</span>}
                                        </td>
                                        <td className="px-4 py-3">{r.titulo}</td>
                                        <td className="px-4 py-3">{r.equipe}</td>
                                        <td className="px-4 py-3">{r.nota_final ?? 'Sem nota'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </motion.div>
        </AppLayout>
    );
}
