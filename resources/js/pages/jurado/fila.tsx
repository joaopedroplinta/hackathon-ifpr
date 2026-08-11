import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, Circle, ClipboardCheck } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Progresso, SubmissaoFila } from '@/types/avaliacao';

interface Props {
    submissoes: SubmissaoFila[];
    progresso: Progresso;
}

export default function FilaJurado({ submissoes, progresso }: Props) {
    const percentual = progresso.total === 0 ? 0 : Math.round((progresso.avaliadas / progresso.total) * 100);

    return (
        <AppLayout breadcrumbs={[{ title: 'Avaliar', href: route('jurado.index') }]}>
            <Head title="Avaliar" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <header className="mb-6">
                    <h1 className="text-2xl font-semibold">Suas submissões</h1>
                    {progresso.total > 0 && (
                        <>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {progresso.avaliadas} de {progresso.total} avaliadas
                            </p>
                            <div className="bg-muted mt-2 h-2 w-full overflow-hidden rounded-full" role="progressbar" aria-valuenow={percentual}>
                                <div className="bg-primary h-full rounded-full transition-all" style={{ width: `${percentual}%` }} />
                            </div>
                        </>
                    )}
                </header>

                {submissoes.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6 text-center">
                        <ClipboardCheck className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Nenhuma submissão atribuída a você ainda.</p>
                        <p className="text-muted-foreground mt-1 text-sm">O organizador ainda não distribuiu as avaliações deste evento.</p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {submissoes.map((s) => (
                            <li key={s.submission_id}>
                                <Link
                                    href={route('jurado.avaliar.show', s.submission_id)}
                                    className="border-sidebar-border/70 dark:border-sidebar-border hover:bg-muted/40 flex items-center justify-between gap-3 rounded-xl border p-4 transition-colors"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">{s.titulo}</p>
                                        <p className="text-muted-foreground truncate text-xs">{s.equipe}</p>
                                    </div>
                                    <span className="flex shrink-0 items-center gap-1.5 text-xs">
                                        {s.enviada ? (
                                            <>
                                                <CheckCircle2 className="h-4 w-4 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                                                Avaliada
                                            </>
                                        ) : (
                                            <>
                                                <Circle className="text-muted-foreground h-4 w-4" aria-hidden="true" />
                                                Pendente
                                            </>
                                        )}
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
