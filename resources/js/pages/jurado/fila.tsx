import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CheckCircle2, Circle, ClipboardCheck } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Progresso, SubmissaoFila } from '@/types/avaliacao';

interface Props {
    submissoes: SubmissaoFila[];
    progresso: Progresso;
}

export default function FilaJurado({ submissoes, progresso }: Props) {
    const percentual = progresso.total === 0 ? 0 : Math.round((progresso.avaliadas / progresso.total) * 100);
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Avaliar', href: route('jurado.index') }]}>
            <Head title="Avaliar" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-5xl p-4 sm:p-8">
                <header className="mb-8">
                    <p className="text-primary mb-2 text-xs font-semibold tracking-widest uppercase">Espaço do jurado</p>
                    <h1 className="text-3xl font-bold tracking-tight">Suas submissões</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Selecione um projeto para avaliar. Você pode salvar o rascunho e continuar depois.
                    </p>
                    {progresso.total > 0 && (
                        <>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {progresso.avaliadas} de {progresso.total} avaliações enviadas · {progresso.total - progresso.avaliadas} pendentes
                            </p>
                            <div
                                className="bg-muted mt-3 h-2 w-full overflow-hidden rounded-full"
                                role="progressbar"
                                aria-label="Progresso das avaliações"
                                aria-valuenow={percentual}
                                aria-valuemin={0}
                                aria-valuemax={100}
                            >
                                <motion.div
                                    className="bg-primary h-full rounded-full"
                                    initial={{ width: 0 }}
                                    animate={{ width: `${percentual}%` }}
                                    transition={reduzMovimento ? { duration: 0 } : { duration: 0.6, ease: 'easeOut' }}
                                />
                            </div>
                        </>
                    )}
                </header>

                {submissoes.length === 0 ? (
                    <div className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <ClipboardCheck className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhuma submissão atribuída a você ainda.</p>
                        <p className="text-muted-foreground text-sm">O organizador ainda não distribuiu as avaliações deste evento.</p>
                    </div>
                ) : (
                    <ul className="border-border bg-card flex flex-col divide-y overflow-hidden rounded-2xl border">
                        {submissoes.map((s) => (
                            <motion.li key={s.submission_id}>
                                <Link
                                    href={route('jurado.avaliar.show', s.submission_id)}
                                    className="hover:bg-muted/50 focus-visible:ring-ring flex min-h-20 flex-wrap items-center justify-between gap-3 p-5 transition-colors focus-visible:ring-2 focus-visible:outline-none sm:p-6"
                                >
                                    <div className="min-w-0">
                                        <p className="font-semibold break-words">{s.titulo}</p>
                                        <p className="text-muted-foreground mt-1 text-sm break-words">{s.equipe}</p>
                                    </div>
                                    <span className="flex shrink-0 items-center gap-1.5 text-xs">
                                        {s.enviada ? (
                                            <>
                                                <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                                                Avaliada
                                            </>
                                        ) : (
                                            <>
                                                <Circle className="text-muted-foreground h-4 w-4" aria-hidden="true" />
                                                Pendente
                                            </>
                                        )}
                                    </span>
                                </Link>
                            </motion.li>
                        ))}
                    </ul>
                )}
            </motion.div>
        </AppLayout>
    );
}
