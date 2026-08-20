import { Head } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Medal, Trophy, Users } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { LinhaPodio, PremioPopular } from '@/types/resultado-publico';

interface Props {
    publicado: boolean;
    evento: { nome: string } | null;
    podio_geral: LinhaPodio[];
    podio_por_trilha: Record<string, LinhaPodio[]>;
    premio_popular: PremioPopular | null;
}

const corDaPosicao: Record<number, string> = {
    1: 'text-amber-500 dark:text-amber-400',
    2: 'text-slate-400 dark:text-slate-300',
    3: 'text-amber-700 dark:text-amber-600',
};

// 2º-1º-3º: ordem visual de um pódio físico, não a ordem de colocação.
const ORDEM_DEGRAUS = [2, 1, 3];

const ALTURA_DEGRAU: Record<number, string> = {
    1: 'h-28 sm:h-32',
    2: 'h-20 sm:h-24',
    3: 'h-14 sm:h-16',
};

const ALTURA_DEGRAU_COMPACTA: Record<number, string> = {
    1: 'h-16 sm:h-20',
    2: 'h-12 sm:h-14',
    3: 'h-8 sm:h-10',
};

function Podio({ linhas, compacto = false }: { linhas: LinhaPodio[]; compacto?: boolean }) {
    const reduzMovimento = useReducedMotion();
    const porPosicao = new Map(linhas.map((linha) => [linha.posicao, linha]));
    const alturas = compacto ? ALTURA_DEGRAU_COMPACTA : ALTURA_DEGRAU;

    const containerVariants: Variants = {
        oculto: {},
        visivel: { transition: { staggerChildren: reduzMovimento ? 0 : 0.15 } },
    };

    const degrauVariants: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 28 },
        visivel: {
            opacity: 1,
            y: 0,
            transition: reduzMovimento ? { duration: 0 } : { type: 'spring', stiffness: 260, damping: 24 },
        },
    };

    return (
        <motion.div
            initial="oculto"
            whileInView="visivel"
            viewport={{ once: true, margin: '-60px' }}
            variants={containerVariants}
            className="flex items-end justify-center gap-3 sm:gap-4"
        >
            {ORDEM_DEGRAUS.map((posicao) => {
                const linha = porPosicao.get(posicao);

                if (!linha) {
                    return null;
                }

                return (
                    <motion.div
                        key={posicao}
                        variants={degrauVariants}
                        className={`flex flex-col items-center gap-2 ${compacto ? 'w-20 sm:w-24' : 'w-24 sm:w-28'}`}
                    >
                        <Medal className={`h-6 w-6 shrink-0 ${corDaPosicao[posicao]}`} aria-hidden="true" />

                        <div className="w-full min-w-0 text-center">
                            <p title={linha.titulo} className="truncate text-sm font-medium">
                                {linha.titulo}
                            </p>
                            <p title={linha.equipe} className="text-muted-foreground truncate text-xs">
                                {linha.equipe}
                                {linha.trilha && ` · ${linha.trilha}`}
                            </p>
                            <p className="mt-1 font-mono text-sm font-semibold tabular-nums">{linha.nota_final.toFixed(2)}</p>
                        </div>

                        <div
                            className={`bg-card border-border/70 flex w-full items-start justify-center rounded-t-lg border border-b-0 pt-2 ${alturas[posicao]}`}
                        >
                            <span className="font-display text-muted-foreground text-lg font-bold">{posicao}º</span>
                        </div>
                    </motion.div>
                );
            })}
        </motion.div>
    );
}

export default function Resultados({ publicado, evento, podio_geral, podio_por_trilha, premio_popular }: Props) {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 12 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.5, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Resultados" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-3xl flex-col gap-16 p-4 pb-24 sm:gap-20 sm:p-6">
                <motion.header initial="oculto" animate="visivel" variants={fadeIn} className="pt-8 text-center sm:pt-16">
                    <p className="text-primary font-mono text-sm">
                        <span aria-hidden="true">$ </span>resultados --status
                    </p>
                    <h1 className="font-display mt-2 text-3xl font-semibold tracking-tight sm:text-5xl">Resultados</h1>
                    {evento && <p className="text-muted-foreground mt-2 text-sm">{evento.nome}</p>}
                </motion.header>

                {!publicado ? (
                    <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="rounded-xl border border-dashed p-10 text-center">
                        <Trophy className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Resultado ainda não publicado</p>
                        <p className="text-muted-foreground mt-1 text-sm">A organização está conferindo as notas antes de divulgar a colocação.</p>
                    </motion.div>
                ) : (
                    <>
                        <section aria-labelledby="podio-geral">
                            <h2 id="podio-geral" className="font-display mb-8 flex items-center justify-center gap-2 text-center font-medium">
                                <Trophy className="h-4 w-4 shrink-0" aria-hidden="true" />
                                Pódio geral
                            </h2>
                            {podio_geral.length === 0 ? (
                                <p className="text-muted-foreground text-center text-sm">Nenhuma submissão pontuada ainda.</p>
                            ) : (
                                <Podio linhas={podio_geral} />
                            )}
                        </section>

                        {Object.keys(podio_por_trilha).length > 0 && (
                            <section aria-labelledby="podio-trilhas">
                                <h2 id="podio-trilhas" className="font-display mb-8 text-center font-medium">
                                    Pódio por trilha
                                </h2>
                                <div className="grid gap-10 sm:grid-cols-2">
                                    {Object.entries(podio_por_trilha).map(([trilha, linhas]) => (
                                        <div key={trilha} className="flex flex-col items-center gap-4">
                                            <p className="text-muted-foreground text-sm font-medium">{trilha}</p>
                                            <Podio linhas={linhas} compacto />
                                        </div>
                                    ))}
                                </div>
                            </section>
                        )}

                        {premio_popular && (
                            <motion.section
                                initial="oculto"
                                whileInView="visivel"
                                viewport={{ once: true, margin: '-60px' }}
                                variants={fadeIn}
                                aria-labelledby="premio-popular"
                                className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6 text-center sm:p-8"
                            >
                                <h2 id="premio-popular" className="flex items-center justify-center gap-2 font-medium">
                                    <Users className="h-4 w-4 shrink-0" aria-hidden="true" />
                                    Prêmio popular
                                </h2>
                                <p className="mt-3 font-medium">{premio_popular.titulo}</p>
                                <p className="text-muted-foreground text-sm">
                                    {premio_popular.equipe} · {premio_popular.votos} {premio_popular.votos === 1 ? 'voto' : 'votos'}
                                </p>
                            </motion.section>
                        )}
                    </>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
