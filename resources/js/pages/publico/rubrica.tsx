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
                <motion.header initial="oculto" animate="visivel" variants={fadeIn} className="pt-8 sm:pt-12">
                    <p className="text-primary font-mono text-sm">
                        <span aria-hidden="true">$ </span>rubrica --status
                    </p>
                    <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Rubrica de avaliação</h1>
                    {evento && <p className="text-muted-foreground mt-2 text-sm">{evento.nome}</p>}
                    <p className="text-muted-foreground mt-2 text-sm">
                        Cada jurado avalia com estes critérios. A nota da avaliação é a média ponderada pelos pesos abaixo.
                    </p>
                </motion.header>

                {criterios.length === 0 ? (
                    <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="rounded-xl border border-dashed p-10 text-center">
                        <ClipboardList className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Rubrica ainda não publicada</p>
                        <p className="text-muted-foreground mt-1 text-sm">A organização ainda está definindo os critérios de avaliação.</p>
                    </motion.div>
                ) : (
                    <>
                        <motion.ol initial="oculto" animate="visivel" variants={listaVariants} className="flex flex-col gap-3">
                            {criterios.map((criterio, indice) => {
                                const proporcao = somaPesos > 0 ? (criterio.peso / somaPesos) * 100 : 0;

                                return (
                                    <motion.li key={criterio.id} variants={itemVariants} className="rounded-xl border p-4">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <p className="font-medium">
                                                {indice + 1}. {criterio.nome}
                                            </p>
                                            <span className="text-muted-foreground flex items-center gap-1 font-mono text-xs">
                                                <Scale className="h-3 w-3 shrink-0" aria-hidden="true" />
                                                peso {criterio.peso} · nota até {criterio.nota_maxima}
                                            </span>
                                        </div>
                                        {criterio.descricao && <p className="text-muted-foreground mt-1 text-sm">{criterio.descricao}</p>}

                                        {/* peso vira largura de verdade -- o próprio critério mais
                                            importante da nota literalmente ocupa mais espaço aqui. */}
                                        <div className="bg-muted mt-3 h-1.5 w-full overflow-hidden rounded-full">
                                            <motion.div
                                                className="bg-verde-brilho h-full rounded-full"
                                                initial={{ width: 0 }}
                                                whileInView={{ width: `${proporcao}%` }}
                                                viewport={{ once: true }}
                                                transition={
                                                    reduzMovimento ? { duration: 0 } : { duration: 0.7, ease: 'easeOut', delay: indice * 0.08 }
                                                }
                                            />
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
