import { Head, Link, usePage } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CalendarCheck, CircleAlert, CircleCheck } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Início', href: '/dashboard' }];

function Cartao({
    icone: Icone,
    corIcone,
    titulo,
    children,
}: {
    icone: typeof CircleCheck;
    corIcone: string;
    titulo: string;
    children: React.ReactNode;
}) {
    return (
        <section className="bg-card border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6 shadow-sm">
            <div className="flex items-start gap-4">
                <span className={`flex size-10 shrink-0 items-center justify-center rounded-lg ${corIcone}`}>
                    <Icone className="h-5 w-5" aria-hidden="true" />
                </span>
                <div className="min-w-0 flex-1">
                    <h2 className="font-display font-medium">{titulo}</h2>
                    <div className="text-muted-foreground mt-1 text-sm leading-relaxed">{children}</div>
                </div>
            </div>
        </section>
    );
}

function CartaoInscricao() {
    const { evento } = usePage<SharedData>().props;

    if (!evento) {
        return (
            <Cartao icone={CalendarCheck} corIcone="bg-muted text-muted-foreground" titulo="Nenhum evento aberto">
                Assim que a organização publicar o próximo hackathon, ele aparece aqui.
            </Cartao>
        );
    }

    if (evento.inscrito) {
        return (
            <Cartao icone={CircleCheck} corIcone="bg-primary/15 text-primary" titulo="Inscrição confirmada">
                Você está inscrito em {evento.nome}. O próximo passo é formar uma equipe.
            </Cartao>
        );
    }

    if (!evento.inscricoes_abertas) {
        return (
            <Cartao icone={CircleAlert} corIcone="bg-amber-500/15 text-amber-600 dark:text-amber-400" titulo="Inscrições fechadas">
                As inscrições para {evento.nome} não estão abertas no momento. Procure a organização se você acha que isso é um engano.
            </Cartao>
        );
    }

    return (
        <Cartao icone={CalendarCheck} corIcone="bg-primary/15 text-primary" titulo="Inscrições abertas">
            <p>Você ainda não está inscrito em {evento.nome}.</p>
            <Button asChild className="mt-4">
                <Link href={route('registration.create')}>Fazer inscrição</Link>
            </Button>
        </Cartao>
    );
}

export default function Dashboard() {
    const { auth } = usePage<SharedData>().props;
    const reduzMovimento = useReducedMotion();

    const primeiroNome = auth.user.name.split(' ')[0];

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Início" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="flex flex-col gap-6 p-4 sm:p-6">
                <header>
                    <p className="text-primary font-mono text-sm">
                        <span aria-hidden="true">$ </span>whoami
                    </p>
                    <h1 className="font-display mt-1 text-2xl font-semibold tracking-tight">Olá, {primeiroNome}</h1>
                </header>

                <CartaoInscricao />
            </motion.div>
        </AppLayout>
    );
}
