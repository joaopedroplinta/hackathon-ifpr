import { Head } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { ClipboardList, Scale } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Criterio } from '@/types/rubrica';

interface Props {
    evento: { nome: string } | null;
    criterios: Criterio[];
}

export default function Rubrica({ evento, criterios }: Props) {
    const somaPesos = criterios.reduce((soma, c) => soma + c.peso, 0);
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 12 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.5, ease: 'easeOut' } },
    };

    const listaVariants: Variants = {
        oculto: {},
        visivel: { transition: { staggerChildren: reduzMovimento ? 0 : 0.1 } },
    };

    const itemVariants: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 14 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.45, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Rubrica" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4 pb-24 sm:p-6">
                <motion.header initial="oculto" animate="visivel" variants={fadeIn} className="pt-8 text-center sm:pt-12">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">Rubrica de avaliação</h1>
                    {evento && <p className="text-muted-foreground mt-2 text-sm">{evento.nome}</p>}
                    <p className="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                        Cada jurado avalia com estes critérios. A nota da avaliação é a média ponderada pelos pesos abaixo.
                    </p>
                </motion.header>

                {criterios.length === 0 ? (
                    <motion.div
                        initial="oculto"
                        animate="visivel"
                        variants={fadeIn}
                        className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center"
                    >
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <ClipboardList className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Rubrica ainda não publicada</p>
                        <p className="text-muted-foreground text-sm">A organização ainda está definindo os critérios de avaliação.</p>
                    </motion.div>
                ) : (
                    <>
                        <motion.ol
                            initial="oculto"
                            animate="visivel"
                            variants={listaVariants}
                            className="border-border bg-card flex flex-col divide-y overflow-hidden rounded-xl border"
                        >
                            {criterios.map((criterio, indice) => {
                                const proporcao = somaPesos > 0 ? (criterio.peso / somaPesos) * 100 : 0;

                                return (
                                    <motion.li key={criterio.id} variants={itemVariants} className="p-4">
                                        <div className="flex items-start gap-3">
                                            <span className="border-border text-muted-foreground flex size-7 shrink-0 items-center justify-center rounded-md border text-xs font-bold tabular-nums">
                                                {String(indice + 1).padStart(2, '0')}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <p className="font-semibold">{criterio.nome}</p>
                                                    <span className="text-muted-foreground flex items-center gap-1 text-xs">
                                                        <Scale className="h-3 w-3 shrink-0" aria-hidden="true" />
                                                        peso {criterio.peso} · nota até {criterio.nota_maxima}
                                                    </span>
                                                </div>
                                                {criterio.descricao && <p className="text-muted-foreground mt-1 text-sm">{criterio.descricao}</p>}

                                                {/* peso vira largura de verdade -- o próprio critério mais
                                                    importante da nota literalmente ocupa mais espaço aqui. */}
                                                <div className="bg-muted mt-3 h-1.5 w-full overflow-hidden rounded-full">
                                                    <motion.div
                                                        className="bg-primary h-full rounded-full"
                                                        initial={{ width: 0 }}
                                                        whileInView={{ width: `${proporcao}%` }}
                                                        viewport={{ once: true }}
                                                        transition={
                                                            reduzMovimento
                                                                ? { duration: 0 }
                                                                : { duration: 0.7, ease: 'easeOut', delay: indice * 0.08 }
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </motion.li>
                                );
                            })}
                        </motion.ol>

                        <p className="text-muted-foreground text-xs">Soma dos pesos: {somaPesos}.</p>
                    </>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
