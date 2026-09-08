import { Head } from '@inertiajs/react';
import { type ReactNode } from 'react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import { CabecalhoPagina } from '@/components/hackathon/pagina';
import RodapePublico from '@/components/hackathon/rodape-publico';

type Props = {
    titulo: string;
    descricao?: ReactNode;
    contexto?: string;
    acao?: ReactNode;
    children: ReactNode;
};

export default function PublicLayout({ titulo, descricao, contexto = 'Hackathon IFPR · Campus Pinhais', acao, children }: Props) {
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title={titulo} />
            <CabecalhoPublico />
            <main
                id="conteudo-publico"
                tabIndex={-1}
                className="mx-auto w-full max-w-7xl scroll-mt-24 px-4 pt-10 pb-20 outline-none sm:px-6 sm:pt-14 lg:px-8"
            >
                <div className="border-border mb-8 border-b pb-8 sm:mb-10 sm:pb-10">
                    <CabecalhoPagina eyebrow={contexto} titulo={titulo} descricao={descricao} acao={acao} />
                </div>
                {children}
            </main>
            <RodapePublico />
        </div>
    );
}
