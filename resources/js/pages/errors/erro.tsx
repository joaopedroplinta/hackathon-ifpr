import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, SearchX, ShieldAlert, Wrench } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

type StatusErro = 403 | 404 | 500 | 503;

const conteudoPorStatus: Record<StatusErro, { titulo: string; descricao: string; icone: typeof ShieldAlert }> = {
    403: {
        titulo: 'Acesso não autorizado',
        descricao: 'Você não tem permissão para ver esta página.',
        icone: ShieldAlert,
    },
    404: {
        titulo: 'Página não encontrada',
        descricao: 'O endereço que você tentou acessar não existe ou foi removido.',
        icone: SearchX,
    },
    500: {
        titulo: 'Algo deu errado',
        descricao: 'Foi um erro do nosso lado, não seu. Tente novamente em instantes.',
        icone: Wrench,
    },
    503: {
        titulo: 'Sistema em manutenção',
        descricao: 'Voltamos em poucos minutos. Tente de novo em instantes.',
        icone: Wrench,
    },
};

/**
 * Uma página para 403, 404, 500 e 503 -- ver bootstrap/app.php. O status vem
 * do servidor, então o texto certo aparece mesmo sem o React saber de
 * antemão qual erro vai acontecer.
 *
 * Não lê `auth` de `usePage`: uma rota inexistente nunca casa com nenhum
 * grupo de rota, então o middleware que compartilha `auth` com o Inertia
 * nunca chega a rodar -- a prop viria vazia.
 */
export default function Erro({ status }: { status: StatusErro }) {
    const { titulo, descricao, icone: Icone } = conteudoPorStatus[status] ?? conteudoPorStatus[500];

    return (
        <AuthLayout title={`${status} — ${titulo}`} description={descricao}>
            <Head title={titulo} />

            <div className="border-border bg-card flex flex-col items-center gap-4 rounded-2xl border p-6 text-center">
                <span className="bg-primary/10 text-primary flex size-12 items-center justify-center rounded-full">
                    <Icone className="size-6" aria-hidden="true" />
                </span>
                <p className="text-muted-foreground text-sm">Código de referência: {status}</p>
                <Button asChild className="w-full">
                    <Link href={route('home')}>
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Voltar para a página inicial
                    </Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
