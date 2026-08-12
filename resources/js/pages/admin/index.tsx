import { Head, Link } from '@inertiajs/react';
import { FileText, Scale, ScanLine, Users } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { PainelOrganizador } from '@/types/admin-dashboard';

const cartoes = [
    {
        chave: 'inscritos' as const,
        titulo: 'Inscritos',
        icon: Users,
        href: null,
    },
    {
        chave: 'equipes_sem_submissao' as const,
        titulo: 'Equipes sem submissão',
        icon: FileText,
        href: 'admin.submissions.index',
    },
    {
        chave: 'atribuicoes_em_aberto' as const,
        titulo: 'Avaliações em aberto',
        icon: Scale,
        href: 'admin.jurados.index',
    },
    {
        chave: 'presenca_hoje' as const,
        titulo: 'Presenças hoje',
        icon: ScanLine,
        href: 'admin.checkin.index',
    },
];

export default function AdminDashboard({ evento, ...numeros }: PainelOrganizador) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Painel', href: route('admin.dashboard') }]}>
            <Head title="Painel do organizador" />

            <div className="mx-auto w-full max-w-4xl p-4">
                <header className="mb-6">
                    <h1 className="text-2xl font-semibold">Painel do organizador</h1>
                    <p className="text-muted-foreground mt-1 text-sm">{evento.nome}</p>
                </header>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {cartoes.map((cartao) => {
                        const Icone = cartao.icon;
                        const valor = numeros[cartao.chave];
                        const conteudo = (
                            <>
                                <Icone className="text-muted-foreground h-5 w-5 shrink-0" aria-hidden="true" />
                                <p className="mt-3 text-3xl font-semibold">{valor}</p>
                                <p className="text-muted-foreground mt-1 text-sm">{cartao.titulo}</p>
                            </>
                        );

                        return cartao.href ? (
                            <Link
                                key={cartao.chave}
                                href={route(cartao.href)}
                                className="border-sidebar-border/70 dark:border-sidebar-border hover:border-primary/50 rounded-xl border p-4 transition-colors sm:p-6"
                            >
                                {conteudo}
                            </Link>
                        ) : (
                            <div key={cartao.chave} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 sm:p-6">
                                {conteudo}
                            </div>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
