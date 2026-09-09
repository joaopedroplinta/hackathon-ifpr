import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight, Check, ShieldCheck } from 'lucide-react';

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
        <div className="bg-background grid min-h-svh lg:grid-cols-[minmax(28rem,0.92fr)_minmax(32rem,1.08fr)]">
            <aside className="event-art relative m-4 hidden min-h-[calc(100svh-2rem)] flex-col justify-between overflow-hidden rounded-[2rem] p-10 lg:flex xl:p-14">
                <Link
                    href={route('home')}
                    className="relative flex w-fit items-center gap-3 rounded-xl text-base font-semibold focus-visible:ring-2 focus-visible:ring-[#d6ecac] focus-visible:ring-offset-4 focus-visible:ring-offset-[#183b2b] focus-visible:outline-none"
                >
                    <span className="flex size-11 items-center justify-center rounded-2xl bg-[#d6ecac] text-[#183b2b]">
                        <AppLogoIcon className="size-6 fill-current" />
                    </span>
                    <span>
                        Hackathon IFPR
                        <span className="block text-xs font-normal text-white/65">Campus Pinhais</span>
                    </span>
                </Link>
                <div className="relative max-w-xl py-12">
                    <p className="mb-5 text-sm font-medium text-[#d6ecac]">Tecnologia e colaboração em movimento</p>
                    <h2 className="text-[clamp(2.75rem,4.5vw,4.75rem)] leading-[1.02] font-bold tracking-[-0.055em]">
                        Entre para transformar uma ideia em projeto.
                    </h2>
                    <ul className="mt-9 grid gap-x-8 gap-y-4 border-l border-white/20 pl-5 text-sm text-white/80 xl:grid-cols-2">
                        {highlights.map((item) => (
                            <li key={item} className="flex items-start gap-3 py-1">
                                <span className="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-[#d6ecac] text-[#183b2b]">
                                    <Check className="size-3.5" strokeWidth={2.5} aria-hidden="true" />
                                </span>
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="relative flex items-center justify-between gap-4 border-t border-white/20 pt-6 text-sm text-white/70">
                    <p className="flex items-center gap-2">
                        <ShieldCheck className="size-4 text-[#d6ecac]" aria-hidden="true" />
                        Acesso seguro ao ambiente do evento
                    </p>
                    <ArrowUpRight className="size-5" aria-hidden="true" />
                </div>
            </aside>

            <div className="flex min-w-0 flex-col px-5 py-4 sm:px-10 sm:py-6 xl:px-16">
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
                <main className="flex flex-1 flex-col items-center justify-center py-8 sm:py-12">
                    <div className="w-full max-w-md">
                        <Link
                            href={route('home')}
                            className="focus-visible:ring-ring mb-10 flex w-fit items-center gap-3 rounded-xl text-sm font-semibold focus-visible:ring-2 focus-visible:ring-offset-4 focus-visible:outline-none lg:hidden"
                        >
                            <span className="bg-primary text-primary-foreground flex size-11 items-center justify-center rounded-2xl">
                                <AppLogoIcon className="size-6 fill-current" />
                            </span>
                            <span>
                                Hackathon IFPR
                                <span className="text-muted-foreground block text-xs font-normal">Campus Pinhais</span>
                            </span>
                        </Link>
                        <div className="mb-8 flex flex-col gap-3 border-b pb-7">
                            <h1 className="text-3xl leading-tight font-bold tracking-[-0.035em] sm:text-4xl">{title}</h1>
                            {description && <p className="text-muted-foreground max-w-sm text-sm leading-6">{description}</p>}
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
