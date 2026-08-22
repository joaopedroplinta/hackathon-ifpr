import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CheckCircle2, ChevronRight, ScanLine, Users } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { PainelOrganizador } from '@/types/admin-dashboard';

/**
 * O que precisa de ação agora, não um placar de vaidade. Só entra aqui o
 * que tem fila real por trás (equipe sem envio, avaliação parada) -- ver
 * .claude/skills/regras-avaliacao: incidente é log, não fila, por isso não
 * aparece como pendência.
 */
const pendencias = [
    {
        chave: 'equipes_sem_submissao' as const,
        titulo: (n: number) => (n === 1 ? '1 equipe sem submissão' : `${n} equipes sem submissão`),
        descricao: 'Ainda não enviaram nada ou só têm rascunho salvo.',
        href: 'painel.submissions.index',
    },
    {
        chave: 'atribuicoes_em_aberto' as const,
        titulo: (n: number) => (n === 1 ? '1 avaliação em aberto' : `${n} avaliações em aberto`),
        descricao: 'Atribuídas a um jurado, ainda não concluídas.',
        href: 'painel.jurados.index',
    },
];

const numeros = [
    { chave: 'inscritos' as const, titulo: 'Inscritos', icon: Users, href: null },
    { chave: 'presenca_hoje' as const, titulo: 'Presenças hoje', icon: ScanLine, href: 'painel.checkin.index' },
];

export default function AdminDashboard({ evento, ...dados }: PainelOrganizador) {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    const abertas = pendencias.filter((p) => dados[p.chave] > 0);

    return (
        <AppLayout breadcrumbs={[{ title: 'Painel', href: route('painel.dashboard') }]}>
            <Head title="Painel do organizador" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4 sm:p-6">
                <header>
                    <h1 className="text-2xl font-bold tracking-tight">Painel do organizador</h1>
                    <p className="text-muted-foreground mt-1 text-sm">{evento.nome}</p>
                </header>

                <section aria-labelledby="pendencias">
                    <h2 id="pendencias" className="mb-3 text-sm font-semibold">
                        Precisa de atenção
                    </h2>

                    {abertas.length === 0 ? (
                        <div className="border-border bg-card flex items-center gap-3 rounded-xl border p-4">
                            <CheckCircle2 className="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                            <p className="text-sm">Tudo em dia — nenhuma submissão ou avaliação pendente.</p>
                        </div>
                    ) : (
                        <ul className="border-border bg-card flex flex-col divide-y overflow-hidden rounded-xl border">
                            {abertas.map((p) => {
                                const n = dados[p.chave];

                                return (
                                    <li key={p.chave}>
                                        <Link href={route(p.href)} className="hover:bg-muted/50 flex items-center gap-4 p-4 transition-colors">
                                            <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-500/15 text-sm font-medium text-amber-700 dark:text-amber-400">
                                                {n}
                                            </span>
                                            <div className="min-w-0 flex-1">
                                                <p className="font-semibold">{p.titulo(n)}</p>
                                                <p className="text-muted-foreground mt-0.5 text-sm">{p.descricao}</p>
                                            </div>
                                            <ChevronRight className="text-muted-foreground hidden size-4 shrink-0 sm:block" aria-hidden="true" />
                                        </Link>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </section>

                <section aria-labelledby="hoje">
                    <h2 id="hoje" className="mb-3 text-sm font-semibold">
                        Hoje
                    </h2>

                    <div className="grid grid-cols-2 gap-3">
                        {numeros.map((item) => {
                            const Icone = item.icon;
                            const conteudo = (
                                <>
                                    <Icone className="text-muted-foreground h-5 w-5 shrink-0" aria-hidden="true" />
                                    <p className="mt-3 text-3xl font-medium tabular-nums">{dados[item.chave]}</p>
                                    <p className="text-muted-foreground mt-1 text-sm">{item.titulo}</p>
                                </>
                            );

                            return item.href ? (
                                <Link key={item.chave} href={route(item.href)} className="border-border bg-card rounded-xl border p-4 sm:p-6">
                                    {conteudo}
                                </Link>
                            ) : (
                                <div key={item.chave} className="border-border bg-card rounded-xl border p-4 sm:p-6">
                                    {conteudo}
                                </div>
                            );
                        })}
                    </div>
                </section>
            </motion.div>
        </AppLayout>
    );
}
