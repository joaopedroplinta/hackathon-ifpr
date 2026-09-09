import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CalendarPlus } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

/**
 * Renderizada pela middleware EnsureEventExists pra toda tela do
 * organizador que depende de um evento em cartaz (agenda, check-in,
 * jurados, rubrica, resultados, certificados, submissões, incidentes,
 * painel). Sem isto, essas rotas devolviam 404 cru -- estado vazio precisa
 * dizer qual é o próximo passo (.claude/rules/frontend.md).
 */
export default function SemEvento() {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Evento', href: route('painel.evento.create') }]}>
            <Head title="Nenhum evento cadastrado" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="p-4 sm:p-8">
                <section className="border-border/70 bg-card/80 relative mx-auto flex max-w-2xl flex-col items-center gap-4 overflow-hidden rounded-3xl border p-8 text-center shadow-sm sm:p-14">
                    <div className="bg-primary absolute inset-x-0 top-0 h-1" aria-hidden="true" />
                    <span className="bg-primary/10 flex size-14 items-center justify-center rounded-2xl">
                        <CalendarPlus className="text-primary size-6" aria-hidden="true" />
                    </span>
                    <h1 className="text-2xl font-medium tracking-[-0.03em]">Nenhum evento cadastrado ainda</h1>
                    <p className="text-muted-foreground max-w-lg text-sm leading-relaxed">
                        Esta e as demais telas do painel (agenda, jurados, avaliação, certificados) só ficam disponíveis depois que a primeira edição
                        do hackathon existir.
                    </p>
                    <Button asChild className="mt-2">
                        <Link href={route('painel.evento.create')}>Criar evento</Link>
                    </Button>
                </section>
            </motion.div>
        </AppLayout>
    );
}
