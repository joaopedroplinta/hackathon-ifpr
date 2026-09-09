import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Menu } from 'lucide-react';
import { useState } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import FlashMessages from '@/components/flash-messages';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';

const linksPublicos = [
    { href: 'agenda.index', label: 'Agenda' },
    { href: 'projetos.index', label: 'Projetos' },
    { href: 'rubrica.show', label: 'Critérios' },
    { href: 'regulamento.show', label: 'Regulamento' },
    { href: 'resultados.show', label: 'Resultados' },
];

export default function CabecalhoPublico() {
    const { auth } = usePage<SharedData>().props;
    const [menuAberto, setMenuAberto] = useState(false);

    const accountLinks = (
        <>
            {!auth.user && (
                <Button asChild variant="ghost" className="h-11">
                    <Link href={route('login')}>Entrar</Link>
                </Button>
            )}
            <Button asChild className="h-11 gap-2 rounded-full px-5">
                <Link href={route(auth.user ? 'dashboard' : 'register')}>
                    {auth.user ? 'Meu painel' : 'Criar conta'}
                    <ArrowUpRight className="size-4" aria-hidden="true" />
                </Link>
            </Button>
        </>
    );

    const navigation = (mobile = false) =>
        linksPublicos.map((link) => {
            const active = route().current(link.href) || (link.href === 'resultados.show' && route().current('resultados.show.edicao'));
            return (
                <Link
                    key={link.href}
                    href={route(link.href)}
                    onClick={() => setMenuAberto(false)}
                    aria-current={active ? 'page' : undefined}
                    className={cn(
                        'focus-visible:outline-ring flex min-h-11 items-center rounded-full px-3 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2',
                        active ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                        mobile && 'text-base',
                    )}
                >
                    {link.label}
                </Link>
            );
        });

    return (
        <>
            <a href="#conteudo-publico" className="skip-link">
                Pular para o conteúdo
            </a>
            <header className="bg-background/95 sticky top-0 z-40 backdrop-blur-md">
                <div className="border-border/70 mx-auto flex min-h-20 w-full max-w-7xl items-center justify-between gap-4 border-b px-4 sm:px-6 lg:px-8">
                    <Link href={route('home')} className="flex shrink-0 items-center gap-3">
                        <span className="bg-primary text-primary-foreground flex size-10 items-center justify-center rounded-xl">
                            <AppLogoIcon className="size-6 fill-current" />
                        </span>
                        <span className="flex flex-col">
                            <span className="text-sm font-bold tracking-tight">Hackathon IFPR</span>
                            <span className="text-muted-foreground text-[11px]">Campus Pinhais</span>
                        </span>
                    </Link>
                    <nav aria-label="Navegação principal" className="bg-muted/60 hidden items-center gap-1 rounded-full p-1 lg:flex">
                        {navigation()}
                    </nav>
                    <div className="hidden items-center gap-2 lg:flex">
                        <AppearanceToggleDropdown />
                        {accountLinks}
                    </div>
                    <div className="flex items-center gap-1 lg:hidden">
                        <AppearanceToggleDropdown />
                        <Sheet open={menuAberto} onOpenChange={setMenuAberto}>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="size-11" aria-label="Abrir menu">
                                    <Menu className="size-5" aria-hidden="true" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="flex w-80 max-w-[90vw] flex-col gap-6">
                                <SheetTitle className="text-left">Explore o hackathon</SheetTitle>
                                <SheetDescription>Programação, projetos e tudo para participar.</SheetDescription>
                                <nav aria-label="Navegação principal móvel" className="flex flex-col gap-1">
                                    {navigation(true)}
                                </nav>
                                <div className="border-border mt-auto flex flex-col gap-2 border-t pt-6">{accountLinks}</div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>
            <FlashMessages />
        </>
    );
}
