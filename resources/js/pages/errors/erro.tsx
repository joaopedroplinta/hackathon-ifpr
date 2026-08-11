import { Head, Link } from '@inertiajs/react';

import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

type StatusErro = 403 | 404 | 500 | 503;

const conteudoPorStatus: Record<StatusErro, { titulo: string; descricao: string }> = {
    403: {
        titulo: 'Acesso não autorizado',
        descricao: 'Você não tem permissão para ver esta página.',
    },
    404: {
        titulo: 'Página não encontrada',
        descricao: 'O endereço que você tentou acessar não existe ou foi removido.',
    },
    500: {
        titulo: 'Algo deu errado',
        descricao: 'Foi um erro do nosso lado, não seu. Tente novamente em instantes.',
    },
    503: {
        titulo: 'Sistema em manutenção',
        descricao: 'Voltamos em poucos minutos. Tente de novo em instantes.',
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
    const { titulo, descricao } = conteudoPorStatus[status] ?? conteudoPorStatus[500];

    return (
        <AuthLayout title={`${status} — ${titulo}`} description={descricao}>
            <Head title={titulo} />

            <Button asChild className="w-full">
                <Link href={route('home')}>Voltar para a página inicial</Link>
            </Button>
        </AuthLayout>
    );
}
