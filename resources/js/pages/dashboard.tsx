import { Head, Link, usePage } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Calendar, Check, ChevronRight, Lock } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type PassoTrilha } from '@/types/dashboard';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Início', href: '/dashboard' }];

interface Props {
    trilha: PassoTrilha[] | null;
}

function ItemTrilha({ passo, indice, total, reduzMovimento }: { passo: PassoTrilha; indice: number; total: number; reduzMovimento: boolean | null }) {
    const concluido = passo.status === 'concluido';
    const bloqueado = passo.status === 'bloqueado';
    const ultimo = indice === total - 1;

    const marcador = (
        <span
            className={`flex size-9 shrink-0 items-center justify-center rounded-full ${
                concluido ? 'bg-primary text-primary-foreground' : bloqueado ? 'bg-muted text-muted-foreground' : 'bg-muted text-foreground'
            }`}
        >
            {concluido ? (
                <Check className="size-4" aria-hidden="true" />
            ) : bloqueado ? (
                <Lock className="size-3.5" aria-hidden="true" />
            ) : (
                <span className="text-sm font-medium">{indice + 1}</span>
            )}
        </span>
    );

    const conteudo = (
        <div className="flex gap-4">
            <div className="flex flex-col items-center">
                {marcador}
                {!ultimo && <span className="bg-border mt-1 w-px flex-1" aria-hidden="true" />}
            </div>

            <div className={`flex-1 items-start justify-between gap-3 sm:flex ${ultimo ? 'pb-1' : 'pb-8'}`}>
                <div>
                    <p className={`font-medium ${bloqueado ? 'text-muted-foreground' : ''}`}>{passo.titulo}</p>
                    <p className="text-muted-foreground mt-1 text-sm leading-relaxed">{passo.descricao}</p>
                </div>

                {passo.href && <ChevronRight className="text-muted-foreground mt-1 hidden size-4 shrink-0 sm:block" aria-hidden="true" />}
            </div>
        </div>
    );

    if (!passo.href) {
        return <li>{conteudo}</li>;
    }

    return (
        <motion.li whileHover={reduzMovimento ? undefined : { x: 2 }} transition={{ type: 'spring', stiffness: 400, damping: 25 }}>
            <Link href={passo.href} className="focus-visible:ring-ring -m-2 block rounded-lg p-2 focus-visible:ring-2 focus-visible:outline-none">
                {conteudo}
            </Link>
        </motion.li>
    );
}

export default function Dashboard({ trilha }: Props) {
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

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="flex flex-col gap-8 p-4 sm:p-6">
                <header>
                    <h1 className="text-2xl font-medium tracking-tight">Olá, {primeiroNome}</h1>
                </header>

                {trilha ? (
                    <section className="bg-card max-w-xl rounded-2xl p-6">
                        <ol className="flex flex-col">
                            {trilha.map((passo, indice) => (
                                <ItemTrilha key={passo.chave} passo={passo} indice={indice} total={trilha.length} reduzMovimento={reduzMovimento} />
                            ))}
                        </ol>
                    </section>
                ) : (
                    <section className="bg-card flex max-w-xl flex-col items-center gap-3 rounded-2xl p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Calendar className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-medium">Nenhum evento aberto</p>
                        <p className="text-muted-foreground text-sm">Assim que a organização publicar o próximo hackathon, ele aparece aqui.</p>
                    </section>
                )}
            </motion.div>
        </AppLayout>
    );
}
