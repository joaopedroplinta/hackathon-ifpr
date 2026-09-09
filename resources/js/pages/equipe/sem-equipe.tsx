import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { ArrowRight, KeyRound, Users } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

interface Props {
    pode_criar: boolean;
    inscricoes_abertas: boolean;
}

export default function SemEquipe({ pode_criar, inscricoes_abertas }: Props) {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Equipe', href: route('teams.show') }]}>
            <Head title="Equipe" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-4xl p-4 sm:p-8">
                <section className="border-border bg-card overflow-hidden rounded-2xl border">
                    <div className="bg-primary/10 border-border flex min-h-36 items-end border-b p-6 sm:p-8">
                        <span className="bg-background flex size-14 items-center justify-center rounded-2xl shadow-sm">
                            <Users className="text-primary size-7" aria-hidden="true" />
                        </span>
                    </div>
                    <div className="p-6 sm:p-8">
                        <h1 className="text-2xl font-bold tracking-tight">Seu projeto começa com uma equipe</h1>

                        {pode_criar ? (
                            <>
                                <p className="text-muted-foreground mt-2 max-w-xl text-sm leading-relaxed">
                                    Comece uma nova equipe e convide outras pessoas, ou use o código de uma equipe que já existe.
                                </p>
                                <div className="mt-6 grid gap-3 sm:grid-cols-2">
                                    <Button asChild>
                                        <Link href={route('teams.create')}>
                                            Criar uma equipe <ArrowRight className="size-4" aria-hidden="true" />
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline">
                                        <Link href={route('teams.join.create')}>
                                            <KeyRound className="size-4" aria-hidden="true" />
                                            Entrar com um código
                                        </Link>
                                    </Button>
                                </div>
                            </>
                        ) : (
                            <p className="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                                {inscricoes_abertas
                                    ? 'Confirme sua inscrição no evento antes de formar uma equipe.'
                                    : 'O prazo para formar equipes já encerrou. Procure a organização se você acha que isso é um engano.'}
                            </p>
                        )}
                    </div>
                </section>
            </motion.div>
        </AppLayout>
    );
}
