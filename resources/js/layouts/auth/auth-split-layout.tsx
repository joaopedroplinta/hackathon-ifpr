import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight, Check } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';

type AuthLayoutProps = {
    children: React.ReactNode;
    title?: string;
    description?: string;
};

const highlights = ['Encontre pessoas para construir junto', 'Acompanhe cada etapa do seu projeto', 'Faça parte da comunidade do IFPR'];

export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="bg-background grid min-h-svh lg:grid-cols-[1fr_1.05fr]">
            <aside className="event-art relative m-4 hidden flex-col justify-between overflow-hidden rounded-[2rem] p-10 lg:flex xl:p-14">
                <Link href={route('home')} className="relative flex w-fit items-center gap-3 text-base font-semibold">
                    <AppLogoIcon className="size-9 fill-current text-[#d6ecac]" />
                    Hackathon IFPR
                </Link>
                <div className="relative py-16">
                    <p className="mb-6 text-xs font-semibold tracking-[0.2em] text-[#d6ecac] uppercase">Campus Pinhais · Tecnologia e colaboração</p>
                    <h2 className="max-w-lg text-[clamp(2.75rem,4.5vw,4.75rem)] leading-[1.05] font-bold tracking-[-0.05em]">
                        Sua próxima
                        <br />
                        grande ideia
                        <br />
                        <span className="text-[#d6ecac]">começa aqui.</span>
                    </h2>
                    <ul className="mt-10 flex flex-col gap-4 text-sm text-white/80">
                        {highlights.map((item) => (
                            <li key={item} className="flex items-start gap-3">
                                <Check className="size-4 shrink-0 text-[#d6ecac]" aria-hidden="true" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="flex items-center justify-between gap-4 border-t border-white/20 pt-6 text-sm text-white/70">
                    <p>Conectar. Criar. Transformar.</p>
                    <ArrowUpRight className="size-5" aria-hidden="true" />
                </div>
            </aside>

            <div className="flex min-w-0 flex-col px-6 py-5 sm:px-10">
                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={route('home')}
                        className="text-muted-foreground hover:text-foreground inline-flex min-h-11 items-center gap-2 text-sm"
                    >
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Voltar ao evento
                    </Link>
                    <AppearanceToggleDropdown />
                </div>
                <main className="flex flex-1 flex-col items-center justify-center py-10">
                    <div className="w-full max-w-sm">
                        <Link href={route('home')} className="mb-8 flex items-center gap-3 text-sm font-semibold lg:hidden">
                            <span className="bg-primary text-primary-foreground flex size-10 items-center justify-center rounded-xl">
                                <AppLogoIcon className="size-6 fill-current" />
                            </span>
                            Hackathon IFPR
                        </Link>
                        <div className="mb-8 flex flex-col gap-3">
                            <h1 className="text-3xl font-bold tracking-tight">{title}</h1>
                            {description && <p className="text-muted-foreground text-sm leading-relaxed">{description}</p>}
                        </div>
                        {children}
                    </div>
                </main>
                <p className="text-muted-foreground pb-2 text-center text-xs leading-relaxed">
                    <Link href={route('privacidade.show')} className="inline-flex min-h-11 items-center underline-offset-4 hover:underline">
                        Privacidade
                    </Link>
                    <span className="px-3" aria-hidden="true">
                        ·
                    </span>
                    <Link href={route('regulamento.show')} className="inline-flex min-h-11 items-center underline-offset-4 hover:underline">
                        Regulamento do evento
                    </Link>
                </p>
            </div>
        </div>
    );
}
