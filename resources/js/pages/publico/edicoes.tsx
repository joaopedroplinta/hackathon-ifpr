import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { History, Trophy } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Edicao } from '@/types/edicao';

interface Props {
    edicoes: Edicao[];
}

function formatarData(iso: string | null): string | null {
    if (!iso) return null;

    return new Date(iso).toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo', day: '2-digit', month: 'long', year: 'numeric' });
}

export default function Edicoes({ edicoes }: Props) {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    const listaVariants: Variants = {
        oculto: {},
        visivel: { transition: { staggerChildren: reduzMovimento ? 0 : 0.08 } },
    };

    const itemVariants: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 14 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.45, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Edições anteriores" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4 pb-24 sm:p-6">
                <motion.header initial="oculto" animate="visivel" variants={fadeIn} className="pt-8 text-center sm:pt-12">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">Edições anteriores</h1>
                    <p className="text-muted-foreground mt-2 text-sm">Resultado publicado de hackathons já encerrados.</p>
                </motion.header>

                {edicoes.length === 0 ? (
                    <motion.div
                        initial="oculto"
                        animate="visivel"
                        variants={fadeIn}
                        className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center"
                    >
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <History className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhuma edição encerrada ainda</p>
                        <p className="text-muted-foreground text-sm">Quando uma edição terminar e o resultado for publicado, ela aparece aqui.</p>
                    </motion.div>
                ) : (
                    <motion.ul
                        initial="oculto"
                        animate="visivel"
                        variants={listaVariants}
                        className="border-border bg-card flex flex-col divide-y overflow-hidden rounded-xl border"
                    >
                        {edicoes.map((edicao) => {
                            const dataEncerramento = formatarData(edicao.encerrado_em);

                            return (
                                <motion.li key={edicao.slug} variants={itemVariants}>
                                    <Link
                                        href={route('resultados.show.edicao', edicao.slug)}
                                        className="hover:bg-muted/50 flex items-center gap-3 p-4 transition-colors"
                                    >
                                        <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-lg">
                                            <Trophy className="h-5 w-5" aria-hidden="true" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-semibold">{edicao.nome}</p>
                                            <p className="text-muted-foreground text-xs">
                                                Edição {edicao.edicao}
                                                {dataEncerramento && ` · encerrada em ${dataEncerramento}`}
                                            </p>
                                        </div>
                                    </Link>
                                </motion.li>
                            );
                        })}
                    </motion.ul>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
