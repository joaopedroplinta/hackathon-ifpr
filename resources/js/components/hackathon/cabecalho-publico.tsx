import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import FlashMessages from '@/components/flash-messages';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { SharedData } from '@/types';

const linksPublicos = [
    { href: 'agenda.index', label: 'Agenda' },
    { href: 'rubrica.show', label: 'Rubrica' },
    { href: 'regulamento.show', label: 'Regulamento' },
    { href: 'projetos.index', label: 'Projetos' },
    { href: 'resultados.show', label: 'Resultados' },
];

/**
 * Cabeçalho das telas públicas (landing, agenda, rubrica, projetos,
 * resultados, validar). Só um lugar decide "o que aparece pra visitante
 * versus logado" -- .claude/rules/frontend.md. Abaixo de `sm`, os links
 * e os botões de conta somem pra dentro de um menu -- sem isso, tudo
 * espreme na mesma linha do logo e quebra (celular é metade do uso no
 * dia do evento -- .claude/rules/frontend.md).
 */
export default function CabecalhoPublico() {
    const { auth } = usePage<SharedData>().props;
    const [menuAberto, setMenuAberto] = useState(false);

    const Logo = (
        <Link href={route('home')} className="font-display flex items-center gap-2 font-semibold tracking-tight">
            <span className="bg-primary text-primary-foreground flex size-8 shrink-0 items-center justify-center rounded-md">
                <AppLogoIcon className="size-5 fill-current" />
            </span>
            Hackathon IFPR
        </Link>
    );

    /**
     * Desktop usa "sm" (36px, tudo bem -- é mouse). No menu mobile o mesmo
     * botão precisa do alvo de toque mínimo confortável (44px), daí o h-11
     * quando `noMenuMobile` é true.
     */
    const contaOuEntrar = (noMenuMobile = false) => {
        const classeAltura = noMenuMobile ? 'h-11' : undefined;

        return auth.user ? (
            <Button asChild size="sm" className={classeAltura}>
                <Link href={route('dashboard')}>Meu painel</Link>
            </Button>
        ) : (
            <>
                <Button asChild variant="ghost" size="sm" className={classeAltura}>
                    <Link href={route('login')}>Entrar</Link>
                </Button>
                <Button asChild size="sm" className={classeAltura}>
                    <Link href={route('register')}>Criar conta</Link>
                </Button>
            </>
        );
    };

    return (
        <>
            {/* sticky + blur: cabeçalho nunca é só decoração aqui -- ele precisa
                continuar legível por cima do hero em qualquer ponto do scroll,
                sem um recorte duro (daí o blur em vez de um bg sólido). */}
            <header className="border-border/60 bg-background/80 supports-[backdrop-filter]:bg-background/60 sticky top-0 z-40 border-b backdrop-blur-md">
                <div className="mx-auto flex w-full max-w-5xl items-center justify-between p-4 sm:p-6">
                    {Logo}

                    {/* Desktop: tudo na mesma linha -- ver comentário do componente. */}
                    <nav className="hidden items-center gap-3 sm:flex">
                        {linksPublicos.map((link) => (
                            <Link
                                key={link.href}
                                href={route(link.href)}
                                className="text-muted-foreground hover:text-foreground text-sm transition-colors"
                            >
                                {link.label}
                            </Link>
                        ))}

                        {contaOuEntrar()}

                        <AppearanceToggleDropdown />
                    </nav>

                    {/* Mobile: menu num painel deslizante, não espremido ao lado do logo. */}
                    <div className="flex items-center gap-1 sm:hidden">
                        <AppearanceToggleDropdown />

                        <Sheet open={menuAberto} onOpenChange={setMenuAberto}>
                            <SheetTrigger asChild>
                                {/* h-11 w-11 (44px): alvo de toque mínimo confortável -- o ícone
                                    "size" padrão do botão (h-10) fica 4px abaixo disso. */}
                                <Button variant="ghost" size="icon" className="h-11 w-11" aria-label="Abrir menu">
                                    <Menu className="size-5" aria-hidden="true" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="right" className="flex w-72 flex-col gap-6">
                                <SheetTitle className="font-display text-left">Menu</SheetTitle>
                                <SheetDescription className="sr-only">Links de navegação do site</SheetDescription>

                                <nav className="flex flex-col gap-1">
                                    {linksPublicos.map((link) => (
                                        <Link
                                            key={link.href}
                                            href={route(link.href)}
                                            onClick={() => setMenuAberto(false)}
                                            className="hover:bg-accent hover:text-accent-foreground rounded-md px-3 py-2.5 text-sm font-medium"
                                        >
                                            {link.label}
                                        </Link>
                                    ))}
                                </nav>

                                <div className="mt-auto flex flex-col gap-2">{contaOuEntrar(true)}</div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>

            <FlashMessages />
        </>
    );
}
