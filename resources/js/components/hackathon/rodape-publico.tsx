import { Link, usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { SharedData } from '@/types';

const colunas = [
    {
        titulo: 'Evento',
        links: [
            { href: 'agenda.index', label: 'Agenda' },
            { href: 'regulamento.show', label: 'Regulamento' },
            { href: 'rubrica.show', label: 'Rubrica' },
            { href: 'resultados.show', label: 'Resultados' },
        ],
    },
    {
        titulo: 'Comunidade',
        links: [
            { href: 'projetos.index', label: 'Projetos' },
            { href: 'edicoes.index', label: 'Edições anteriores' },
        ],
    },
    {
        titulo: 'Legal',
        links: [
            { href: 'privacidade.show', label: 'Privacidade' },
            { href: 'cookies.show', label: 'Cookies' },
        ],
    },
];

/**
 * Rodapé das telas públicas -- par do CabecalhoPublico. Colunas por tema em
 * vez de uma linha só de links: com o site inteiro migrado pro padrão
 * "SaaS denso", uma única linha de 3 links ficava fina demais perto do
 * hero centralizado da home. Mesma lista de páginas de sempre, só agrupada.
 */
export default function RodapePublico() {
    const { app_version } = usePage<SharedData>().props;

    return (
        <footer className="border-border border-t">
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-10 p-4 py-12 sm:p-6 sm:py-16">
                <div className="grid gap-10 sm:grid-cols-[1.4fr_1fr_1fr_1fr]">
                    <div className="flex flex-col gap-3">
                        <Link href={route('home')} className="font-display flex items-center gap-2 font-semibold tracking-tight">
                            <span className="bg-primary text-primary-foreground flex size-8 shrink-0 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-5 fill-current" />
                            </span>
                            Hackathon IFPR
                        </Link>
                        <p className="text-muted-foreground max-w-xs text-sm leading-relaxed">
                            Instituto Federal do Paraná — Campus Pinhais. Inscrição, submissão e avaliação em um lugar só.
                        </p>
                        <a
                            href="https://github.com/joaopedroplinta/hackathon-ifpr"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-muted-foreground hover:text-foreground text-sm transition-colors"
                        >
                            Repositório no GitHub
                        </a>
                    </div>

                    {colunas.map((coluna) => (
                        <nav key={coluna.titulo} aria-labelledby={`rodape-${coluna.titulo}`}>
                            <p id={`rodape-${coluna.titulo}`} className="text-foreground text-sm font-semibold">
                                {coluna.titulo}
                            </p>
                            <ul className="mt-3 flex flex-col gap-2">
                                {coluna.links.map((link) => (
                                    <li key={link.href}>
                                        <Link
                                            href={route(link.href)}
                                            className="text-muted-foreground hover:text-foreground text-sm transition-colors"
                                        >
                                            {link.label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    ))}
                </div>

                <div className="border-border flex flex-wrap items-center justify-between gap-3 border-t pt-6 text-xs">
                    <p className="text-muted-foreground">© {new Date().getFullYear()} Instituto Federal do Paraná.</p>
                    <span className="text-muted-foreground font-mono">{app_version}</span>
                </div>
            </div>
        </footer>
    );
}
