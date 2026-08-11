import { Link, usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';

/**
 * Cabeçalho das telas públicas (landing, agenda, e futuramente vitrine de
 * projetos e resultados). Só um lugar decide "o que aparece pra visitante
 * versus logado" -- .claude/rules/frontend.md.
 */
export default function CabecalhoPublico() {
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="mx-auto flex w-full max-w-5xl items-center justify-between p-4 sm:p-6">
            <Link href={route('home')} className="flex items-center gap-2 font-medium">
                <AppLogoIcon className="size-7 fill-current" />
                Hackathon IFPR
            </Link>

            <nav className="flex items-center gap-3">
                <Link href={route('agenda.index')} className="text-muted-foreground hover:text-foreground text-sm">
                    Agenda
                </Link>
                <Link href={route('rubrica.show')} className="text-muted-foreground hover:text-foreground text-sm">
                    Rubrica
                </Link>
                <Link href={route('resultados.show')} className="text-muted-foreground hover:text-foreground text-sm">
                    Resultados
                </Link>

                {auth.user ? (
                    <Button asChild size="sm">
                        <Link href={route('dashboard')}>Meu painel</Link>
                    </Button>
                ) : (
                    <>
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('login')}>Entrar</Link>
                        </Button>
                        <Button asChild size="sm">
                            <Link href={route('register')}>Criar conta</Link>
                        </Button>
                    </>
                )}
            </nav>
        </header>
    );
}
