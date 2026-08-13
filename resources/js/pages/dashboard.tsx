import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarCheck, CircleAlert, CircleCheck } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Início', href: '/dashboard' }];

function CartaoInscricao() {
    const { evento } = usePage<SharedData>().props;

    if (!evento) {
        return (
            <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                <h2 className="font-medium">Nenhum evento aberto</h2>
                <p className="text-muted-foreground mt-1 text-sm">Assim que a organização publicar o próximo hackathon, ele aparece aqui.</p>
            </section>
        );
    }

    if (evento.inscrito) {
        return (
            <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                <div className="flex items-start gap-3">
                    <CircleCheck className="mt-0.5 h-5 w-5 shrink-0 text-green-600" aria-hidden="true" />
                    <div>
                        <h2 className="font-medium">Inscrição confirmada</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Você está inscrito em {evento.nome}. O próximo passo é formar uma equipe.
                        </p>
                    </div>
                </div>
            </section>
        );
    }

    if (!evento.inscricoes_abertas) {
        return (
            <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                <div className="flex items-start gap-3">
                    <CircleAlert className="mt-0.5 h-5 w-5 shrink-0 text-amber-600" aria-hidden="true" />
                    <div>
                        <h2 className="font-medium">Inscrições fechadas</h2>
                        <p className="text-muted-foreground mt-1 text-sm">
                            As inscrições para {evento.nome} não estão abertas no momento. Procure a organização se você acha que isso é um engano.
                        </p>
                    </div>
                </div>
            </section>
        );
    }

    return (
        <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
            <div className="flex items-start gap-3">
                <CalendarCheck className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
                <div>
                    <h2 className="font-medium">Inscrições abertas</h2>
                    <p className="text-muted-foreground mt-1 text-sm">Você ainda não está inscrito em {evento.nome}.</p>
                    <Button asChild className="mt-4">
                        <Link href={route('registration.create')}>Fazer inscrição</Link>
                    </Button>
                </div>
            </div>
        </section>
    );
}

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Início" />

            <div className="flex flex-col gap-4 p-4">
                <CartaoInscricao />
            </div>
        </AppLayout>
    );
}
