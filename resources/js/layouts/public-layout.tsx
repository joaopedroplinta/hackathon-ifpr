import { Head } from '@inertiajs/react';
import { type ReactNode } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
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
        <div className="public-experience bg-background text-foreground min-h-svh">
            <Head title={titulo} />
            <CabecalhoPublico />
            <main
                id="conteudo-publico"
                tabIndex={-1}
                className="mx-auto w-full max-w-7xl scroll-mt-24 px-4 pt-10 pb-20 outline-none sm:px-6 sm:pt-14 lg:px-8"
            >
                <header className="public-page-heading bg-secondary/60 relative mb-10 flex flex-col justify-between gap-6 overflow-hidden rounded-[1.75rem] p-6 sm:p-10 lg:flex-row lg:items-end">
                    <AppLogoIcon
                        aria-hidden="true"
                        className="text-primary/[0.06] pointer-events-none absolute -top-10 -right-8 size-64 rotate-12 fill-current"
                    />
                    <div className="relative min-w-0">
                        <p className="text-muted-foreground mb-4 max-w-xl text-sm">{contexto}</p>
                        <h1 className="text-[clamp(2.5rem,5vw,4.5rem)] leading-[1.05] font-semibold tracking-[-0.055em] text-balance">{titulo}</h1>
                        {descricao && <div className="text-muted-foreground mt-5 max-w-2xl text-base leading-relaxed">{descricao}</div>}
                    </div>
                    {acao && <div className="relative flex shrink-0 flex-wrap gap-2">{acao}</div>}
                </header>
                {children}
            </main>
            <RodapePublico />
        </div>
    );
}
