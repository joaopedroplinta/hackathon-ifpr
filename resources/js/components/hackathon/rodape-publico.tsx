import { Link, usePage } from '@inertiajs/react';

import { SharedData } from '@/types';

/**
 * Rodapé das telas públicas -- par do CabecalhoPublico. A versão só aparecia
 * na sidebar autenticada; quem visita sem login (a maioria no dia do evento)
 * não via nada.
 */
export default function RodapePublico() {
    const { app_version } = usePage<SharedData>().props;

    return (
        <footer className="mx-auto flex w-full max-w-5xl flex-wrap items-center justify-between gap-3 p-4 text-sm sm:p-6">
            <nav className="flex flex-wrap gap-x-4 gap-y-1">
                <Link href={route('regulamento.show')} className="text-muted-foreground hover:text-foreground">
                    Regulamento
                </Link>
                <a
                    href="https://github.com/joaopedroplinta/hackathon-ifpr"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-muted-foreground hover:text-foreground"
                >
                    Repositório
                </a>
            </nav>

            <span className="text-muted-foreground font-mono text-xs">{app_version}</span>
        </footer>
    );
}
