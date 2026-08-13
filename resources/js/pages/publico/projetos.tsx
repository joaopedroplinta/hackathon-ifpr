import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, Rocket, Vote } from 'lucide-react';
import { useState } from 'react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
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

    const votar = (submissionId: number) => {
        setVotandoEm(submissionId);
        router.post(route('votos.store'), { submission_id: submissionId }, { preserveScroll: true, onFinish: () => setVotandoEm(null) });
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Projetos" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="font-display text-2xl font-semibold tracking-tight">Projetos</h1>
                    {evento && <p className="text-muted-foreground mt-1 text-sm">{evento.nome}</p>}
                </header>

                {votacaoAberta && !podeVotar && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 text-sm">
                        {auth.user ? (
                            <>Você precisa estar inscrito neste evento para votar.</>
                        ) : (
                            <>
                                <Link href={route('login')} className="text-primary hover:underline">
                                    Entre
                                </Link>{' '}
                                e inscreva-se no evento para votar no seu projeto favorito.
                            </>
                        )}
                    </div>
                )}

                {submissoes.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border border-dashed p-10 text-center">
                        <Rocket className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Nenhum projeto enviado ainda</p>
                        <p className="text-muted-foreground mt-1 text-sm">Volte depois que o prazo de submissão fechar.</p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {submissoes.map((submissao) => {
                            const jaVotouNesta = jaVotouEm === submissao.id;

                            return (
                                <li key={submissao.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                                    <p className="font-medium">{submissao.titulo}</p>
                                    <p className="text-muted-foreground text-xs">{submissao.equipe}</p>
                                    {submissao.resumo && <p className="mt-2 text-sm">{submissao.resumo}</p>}

                                    {votacaoAberta && podeVotar && (
                                        <div className="mt-3">
                                            {jaVotouNesta ? (
                                                <span className="flex items-center gap-1.5 text-sm text-emerald-700 dark:text-emerald-400">
                                                    <CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden="true" />
                                                    Seu voto
                                                </span>
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
                                </li>
                            );
                        })}
                    </ul>
                )}
            </main>
        </div>
    );
}
