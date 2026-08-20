import { Head } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CalendarClock, CalendarX2, Download, MapPin, Mic } from 'lucide-react';
import { useEffect, useState } from 'react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Button } from '@/components/ui/button';
import { EventoPublico, ItemAgenda, TipoItemAgenda } from '@/types/publico';

interface Props {
    evento: EventoPublico | { nome: string } | null;
    itens: ItemAgenda[];
}

const corDoTipo: Record<TipoItemAgenda, string> = {
    palestra: 'bg-blue-500/15 text-blue-700 dark:text-blue-400',
    workshop: 'bg-violet-500/15 text-violet-700 dark:text-violet-400',
    checkpoint: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    refeicao: 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
    deadline: 'bg-red-500/15 text-red-700 dark:text-red-400',
};

function formatarHora(iso: string): string {
    return new Date(iso).toLocaleTimeString('pt-BR', { timeZone: 'America/Sao_Paulo', hour: '2-digit', minute: '2-digit' });
}

function chaveDoDia(iso: string): string {
    return new Date(iso).toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo', day: '2-digit', month: 'long', weekday: 'long' });
}

/** Agora, atualizado a cada 30s -- não precisa de mais precisão que isso pra destacar "acontecendo agora". */
function useAgora(): number {
    const [agora, setAgora] = useState(() => Date.now());

    useEffect(() => {
        const id = window.setInterval(() => setAgora(Date.now()), 30_000);

        return () => window.clearInterval(id);
    }, []);

    return agora;
}

export default function Agenda({ evento, itens }: Props) {
    const agora = useAgora();
    const reduzMovimento = useReducedMotion();

    const dias = itens.reduce<Record<string, ItemAgenda[]>>((acc, item) => {
        const dia = chaveDoDia(item.inicia_em);
        (acc[dia] ??= []).push(item);

        return acc;
    }, {});

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 12 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.5, ease: 'easeOut' } },
    };

    const listaVariants: Variants = {
        oculto: {},
        visivel: { transition: { staggerChildren: reduzMovimento ? 0 : 0.08 } },
    };

    const itemVariants: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, x: -12 },
        visivel: { opacity: 1, x: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Agenda" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-3xl flex-col gap-10 p-4 pb-24 sm:p-6">
                <motion.header
                    initial="oculto"
                    animate="visivel"
                    variants={fadeIn}
                    className="flex flex-wrap items-start justify-between gap-3 pt-8 sm:pt-12"
                >
                    <div>
                        <h1 className="text-3xl font-medium tracking-tight sm:text-4xl">Agenda</h1>
                        {evento && <p className="text-muted-foreground mt-2 text-sm">{evento.nome}</p>}
                    </div>

                    {itens.length > 0 && (
                        <Button asChild variant="outline">
                            <a href={route('agenda.ics')}>
                                <Download className="h-4 w-4" aria-hidden="true" />
                                Baixar (.ics)
                            </a>
                        </Button>
                    )}
                </motion.header>

                {itens.length === 0 ? (
                    <motion.div
                        initial="oculto"
                        animate="visivel"
                        variants={fadeIn}
                        className="bg-card flex flex-col items-center gap-3 rounded-2xl p-10 text-center"
                    >
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <CalendarX2 className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-medium">Agenda ainda não publicada</p>
                        <p className="text-muted-foreground text-sm">Assim que a organização publicar os horários, eles aparecem aqui.</p>
                    </motion.div>
                ) : (
                    Object.entries(dias).map(([dia, itensDoDia]) => (
                        <section key={dia} aria-labelledby={`dia-${dia}`}>
                            <h2 id={`dia-${dia}`} className="text-muted-foreground mb-4 text-xs tracking-wide uppercase">
                                {dia}
                            </h2>

                            <motion.ol
                                initial="oculto"
                                whileInView="visivel"
                                viewport={{ once: true, margin: '-40px' }}
                                variants={listaVariants}
                                className="flex flex-col"
                            >
                                {itensDoDia.map((item, indice) => {
                                    const emAndamento = agora >= new Date(item.inicia_em).getTime() && agora <= new Date(item.termina_em).getTime();
                                    const ultimoDoDia = indice === itensDoDia.length - 1;

                                    return (
                                        <motion.li
                                            key={item.id}
                                            variants={itemVariants}
                                            className="grid grid-cols-[1.25rem_1fr] gap-x-3 sm:grid-cols-[1.5rem_1fr]"
                                        >
                                            {/* trilho do tempo: bolinha por item, ligada por uma linha -- a mesma
                                                metáfora de log/timeline do resto da página pública. */}
                                            <div className="flex flex-col items-center">
                                                <span className="relative mt-1.5 flex size-3 shrink-0 items-center justify-center">
                                                    {emAndamento && (
                                                        <span
                                                            className="bg-primary absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                                                            aria-hidden="true"
                                                        />
                                                    )}
                                                    <span
                                                        className={`relative inline-flex size-2.5 rounded-full ${
                                                            emAndamento || item.destaque ? 'bg-primary' : 'bg-border'
                                                        }`}
                                                    />
                                                </span>
                                                {!ultimoDoDia && <span className="bg-border mt-1 w-px flex-1" aria-hidden="true" />}
                                            </div>

                                            <div className={`bg-card mb-3 rounded-2xl p-4 ${emAndamento ? 'ring-primary/30 ring-2' : ''}`}>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="text-muted-foreground text-sm tabular-nums">
                                                        {formatarHora(item.inicia_em)}–{formatarHora(item.termina_em)}
                                                    </span>
                                                    <span className={`inline-block rounded-full px-2 py-0.5 text-xs ${corDoTipo[item.tipo]}`}>
                                                        {item.tipo_label}
                                                    </span>
                                                    {item.trilha && (
                                                        <span
                                                            className="inline-flex items-center gap-1.5 text-xs"
                                                            style={{ color: item.trilha.cor ?? undefined }}
                                                        >
                                                            <span
                                                                className="h-2 w-2 rounded-full"
                                                                style={{ backgroundColor: item.trilha.cor ?? 'currentColor' }}
                                                                aria-hidden="true"
                                                            />
                                                            {item.trilha.nome}
                                                        </span>
                                                    )}
                                                    {emAndamento && (
                                                        <span role="status" className="text-primary flex items-center gap-1 text-xs font-medium">
                                                            <CalendarClock className="h-3 w-3 shrink-0" aria-hidden="true" />
                                                            Acontecendo agora
                                                        </span>
                                                    )}
                                                </div>

                                                <p className="mt-2 font-medium">{item.titulo}</p>
                                                {item.descricao && <p className="text-muted-foreground mt-1 text-sm">{item.descricao}</p>}

                                                {(item.local || item.palestrante) && (
                                                    <div className="text-muted-foreground mt-2 flex flex-wrap gap-3 text-xs">
                                                        {item.local && (
                                                            <span className="flex items-center gap-1">
                                                                <MapPin className="h-3 w-3 shrink-0" aria-hidden="true" />
                                                                {item.local}
                                                            </span>
                                                        )}
                                                        {item.palestrante && (
                                                            <span className="flex items-center gap-1">
                                                                <Mic className="h-3 w-3 shrink-0" aria-hidden="true" />
                                                                {item.palestrante}
                                                            </span>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </motion.li>
                                    );
                                })}
                            </motion.ol>
                        </section>
                    ))
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
