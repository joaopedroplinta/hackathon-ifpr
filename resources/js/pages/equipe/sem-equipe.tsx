import { Head, Link, usePage } from '@inertiajs/react';
import { Users } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';

interface Props {
    pode_criar: boolean;
    inscricoes_abertas: boolean;
}

export default function SemEquipe({ pode_criar, inscricoes_abertas }: Props) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={[{ title: 'Equipe', href: route('teams.show') }]}>
            <Head title="Equipe" />

            <div className="mx-auto w-full max-w-2xl p-4">
                {/* Cobre, entre outros casos, o convite recusado ao aceitar
                    (expirado, já aceito, e-mail trocado) -- quem clica no
                    link do e-mail cai aqui se ainda não tem equipe. */}
                {flash?.erro && (
                    <div role="alert" className="mb-4 rounded-xl border border-red-600/30 bg-red-600/10 p-4 text-sm text-red-800 dark:text-red-300">
                        {flash.erro}
                    </div>
                )}

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-8 text-center">
                    <Users className="text-muted-foreground mx-auto h-10 w-10" aria-hidden="true" />

                    <h1 className="mt-4 text-xl font-semibold">Você ainda não tem equipe</h1>

                    {pode_criar ? (
                        <>
                            <p className="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                                Crie uma equipe e compartilhe o código de convite, ou peça o código de quem já criou a sua.
                            </p>
                            <Button asChild className="mt-6">
                                <Link href={route('teams.create')}>Criar equipe</Link>
                            </Button>
                        </>
                    ) : (
                        <p className="text-muted-foreground mx-auto mt-2 max-w-md text-sm">
                            {inscricoes_abertas
                                ? 'Confirme sua inscrição no evento antes de formar uma equipe.'
                                : 'O prazo para formar equipes já encerrou. Procure a organização se você acha que isso é um engano.'}
                        </p>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
