import { Head, Link, router, usePage } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CheckCircle2, LoaderCircle, Rocket, Vote } from 'lucide-react';
import { useState } from 'react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { SubmissaoVitrine } from '@/types/projeto';

interface Props {
    evento: { nome: string } | null;
    submissoes: SubmissaoVitrine[];
    votacao_aberta: boolean;
    pode_votar: boolean;
    ja_votou_em: number | null;
}

export default function Projetos({ evento, submissoes, votacao_aberta: votacaoAberta, pode_votar: podeVotar, ja_votou_em: jaVotouEm }: Props) {
    const { auth } = usePage<SharedData>().props;
    const [votandoEm, setVotandoEm] = useState<number | null>(null);
    const reduzMovimento = useReducedMotion();

    const votar = (submissionId: number) => {
        setVotandoEm(submissionId);
        router.post(route('votos.store'), { submission_id: submissionId }, { preserveScroll: true, onFinish: () => setVotandoEm(null) });
    };

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 12 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.5, ease: 'easeOut' } },
    };

    const listaVariants: Variants = {
        oculto: {},
        visivel: { transition: { staggerChildren: reduzMovimento ? 0 : 0.08 } },
    };

    const itemVariants: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 16 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.45, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Projetos" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4 pb-24 sm:p-6">
                <motion.header initial="oculto" animate="visivel" variants={fadeIn} className="pt-8 text-center sm:pt-12">
                    <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">Projetos</h1>
                    {evento && <p className="text-muted-foreground mt-2 text-sm">{evento.nome}</p>}
                </motion.header>

                {votacaoAberta && !podeVotar && (
                    <motion.div
                        initial="oculto"
                        animate="visivel"
                        variants={fadeIn}
                        className="border-border bg-muted/30 rounded-xl border p-4 text-sm"
                    >
                        {auth.user ? (
                            <>Você precisa estar inscrito neste evento para votar.</>
                        ) : (
                            <>
                                <Link href={route('login')} className="text-verde-ifpr hover:underline">
                                    Entre
                                </Link>{' '}
                                e inscreva-se no evento para votar no seu projeto favorito.
                            </>
                        )}
                    </motion.div>
                )}

                {submissoes.length === 0 ? (
                    <motion.div
                        initial="oculto"
                        animate="visivel"
                        variants={fadeIn}
                        className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center"
                    >
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Rocket className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhum projeto enviado ainda</p>
                        <p className="text-muted-foreground text-sm">Volte depois que o prazo de submissão fechar.</p>
                    </motion.div>
                ) : (
                    <motion.ul initial="oculto" animate="visivel" variants={listaVariants} className="flex flex-col gap-4">
                        {submissoes.map((submissao) => {
                            const jaVotouNesta = jaVotouEm === submissao.id;

                            return (
                                <motion.li
                                    key={submissao.id}
                                    variants={itemVariants}
                                    whileHover={{ y: -2 }}
                                    transition={{ type: 'spring', stiffness: 400, damping: 25 }}
                                    className="border-border bg-card rounded-xl border p-5 sm:p-6"
                                >
                                    <p className="font-semibold">{submissao.titulo}</p>
                                    <p className="text-muted-foreground text-xs">{submissao.equipe}</p>
                                    {submissao.resumo && <p className="mt-2 text-sm leading-relaxed">{submissao.resumo}</p>}

                                    {votacaoAberta && podeVotar && (
                                        <div className="mt-4">
                                            {jaVotouNesta ? (
                                                <motion.span
                                                    initial={reduzMovimento ? false : { opacity: 0, scale: 0.85 }}
                                                    animate={{ opacity: 1, scale: 1 }}
                                                    transition={{ type: 'spring', stiffness: 400, damping: 20 }}
                                                    className="flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-400"
                                                >
                                                    <CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden="true" />
                                                    Seu voto
                                                </motion.span>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    disabled={jaVotouEm !== null || votandoEm === submissao.id}
                                                    onClick={() => votar(submissao.id)}
                                                >
                                                    {votandoEm === submissao.id ? (
                                                        <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                                                    ) : (
                                                        <Vote className="h-4 w-4" aria-hidden="true" />
                                                    )}
                                                    Votar
                                                </Button>
                                            )}
                                        </div>
                                    )}
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
