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
        <AppLayout breadcrumbs={[{ title: 'Evento', href: route('admin.evento.create') }]}>
            <Head title="Nenhum evento cadastrado" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="p-4 sm:p-6">
                <section className="border-border bg-card mx-auto flex max-w-xl flex-col items-center gap-3 rounded-xl border p-10 text-center">
                    <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                        <CalendarPlus className="text-muted-foreground size-5" aria-hidden="true" />
                    </span>
                    <p className="font-semibold">Nenhum evento cadastrado ainda</p>
                    <p className="text-muted-foreground text-sm">
                        Esta e as demais telas do painel (agenda, jurados, avaliação, certificados) só ficam disponíveis depois que a primeira edição
                        do hackathon existir.
                    </p>
                    <Button asChild className="mt-2">
                        <Link href={route('admin.evento.create')}>Criar evento</Link>
                    </Button>
                </section>
            </motion.div>
        </AppLayout>
    );
}
