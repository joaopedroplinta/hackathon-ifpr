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

            <div className="border-border bg-card relative overflow-hidden rounded-3xl border p-6 sm:p-8">
                <span
                    className="text-primary/[0.06] pointer-events-none absolute -top-12 -right-2 font-mono text-[10rem] leading-none font-semibold"
                    aria-hidden="true"
                >
                    {status}
                </span>
                <div className="relative flex flex-col items-start gap-5">
                    <span className="bg-primary/10 text-primary ring-primary/10 flex size-12 items-center justify-center rounded-2xl ring-4">
                        <Icone className="size-6" aria-hidden="true" />
                    </span>
                    <div>
                        <p className="text-muted-foreground text-sm">Código de referência</p>
                        <p className="mt-1 font-mono text-2xl font-semibold tabular-nums">{status}</p>
                    </div>
                </div>
                <Button asChild className="relative mt-8 h-11 w-full">
                    <Link href={route('home')}>
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Voltar para a página inicial
                    </Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
