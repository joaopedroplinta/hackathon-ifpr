import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Users } from 'lucide-react';

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

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-8 text-center">
                    <Users className="text-muted-foreground mx-auto h-10 w-10" aria-hidden="true" />

                    <h1 className="font-display mt-4 text-xl font-semibold tracking-tight">Você ainda não tem equipe</h1>

                    {pode_criar ? (
                        <>
                            <p className="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                                Crie uma equipe e compartilhe o código de convite, ou peça o código de quem já criou a sua.
                            </p>
                            <div className="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                                <Button asChild>
                                    <Link href={route('teams.create')}>Criar equipe</Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href={route('teams.join.create')}>Entrar com um código</Link>
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
                </section>
            </motion.div>
        </AppLayout>
    );
}
