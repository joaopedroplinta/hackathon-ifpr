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

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <header className="mb-6">
                    <h1 className="text-2xl font-medium tracking-tight">Suas submissões</h1>
                    {progresso.total > 0 && (
                        <>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {progresso.avaliadas} de {progresso.total} avaliadas
                            </p>
                            <div className="bg-muted mt-2 h-2 w-full overflow-hidden rounded-full" role="progressbar" aria-valuenow={percentual}>
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
                    <div className="bg-card flex flex-col items-center gap-3 rounded-2xl p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <ClipboardCheck className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-medium">Nenhuma submissão atribuída a você ainda.</p>
                        <p className="text-muted-foreground text-sm">O organizador ainda não distribuiu as avaliações deste evento.</p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {submissoes.map((s) => (
                            <motion.li
                                key={s.submission_id}
                                whileHover={reduzMovimento ? undefined : { y: -2 }}
                                transition={{ type: 'spring', stiffness: 400, damping: 25 }}
                            >
                                <Link
                                    href={route('jurado.avaliar.show', s.submission_id)}
                                    className="bg-card flex min-h-11 items-center justify-between gap-3 rounded-2xl p-4"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">{s.titulo}</p>
                                        <p className="text-muted-foreground truncate text-xs">{s.equipe}</p>
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
