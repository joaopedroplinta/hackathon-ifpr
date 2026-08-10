import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarClock, ClipboardList, Rocket, UsersRound } from 'lucide-react';

import ContadorEvento from '@/components/hackathon/contador-evento';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { EventoPublico } from '@/types/publico';

interface Props {
    evento: EventoPublico | null;
}

const passos = [
    {
        icone: UsersRound,
        titulo: 'Forme sua equipe',
        texto: 'Crie uma equipe ou entre em uma existente pelo código de convite.',
    },
    {
        icone: ClipboardList,
        titulo: 'Desenvolva o projeto',
        texto: 'Use a agenda para acompanhar oficinas e checkpoints durante o evento.',
    },
    {
        icone: Rocket,
        titulo: 'Envie até o prazo',
        texto: 'Repositório, vídeo e descrição — tudo pelo sistema, com histórico de versões.',
    },
];

export default function Inicio({ evento }: Props) {
    const { auth } = usePage<SharedData>().props;

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Início" />

            <header className="mx-auto flex w-full max-w-5xl items-center justify-between p-4 sm:p-6">
                <span className="flex items-center gap-2 font-medium">
                    <AppLogoIcon className="size-7 fill-current" />
                    Hackathon IFPR
                </span>

                <nav className="flex items-center gap-3">
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

            <main className="mx-auto flex w-full max-w-5xl flex-col gap-16 p-4 pb-24 sm:p-6">
                {evento ? (
                    <>
                        <section className="flex flex-col items-center gap-8 pt-8 text-center sm:pt-16">
                            <div>
                                <p className="text-muted-foreground text-sm font-medium tracking-wide uppercase">
                                    {evento.edicao}ª edição — {evento.situacao_label}
                                </p>
                                <h1 className="mt-2 text-3xl font-semibold sm:text-5xl">{evento.nome}</h1>
                                {evento.descricao && <p className="text-muted-foreground mx-auto mt-4 max-w-2xl text-balance">{evento.descricao}</p>}
                            </div>

                            {evento.situacao === 'running' ? (
                                <p role="status" className="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400">
                                    <CalendarClock className="h-4 w-4 shrink-0" aria-hidden="true" />O evento está acontecendo agora.
                                </p>
                            ) : evento.situacao === 'finished' ? (
                                <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                                    <CalendarClock className="h-4 w-4 shrink-0" aria-hidden="true" />
                                    Esta edição já terminou. Resultados em breve.
                                </p>
                            ) : evento.inicia_em ? (
                                <ContadorEvento alvo={evento.inicia_em} rotulo="Faltam para o início" />
                            ) : null}

                            <div className="flex flex-wrap items-center justify-center gap-3">
                                {!auth.user && (
                                    <Button asChild size="lg">
                                        <Link href={route('register')}>Criar conta e participar</Link>
                                    </Button>
                                )}

                                {auth.user && evento.inscricoes_abertas && (
                                    <Button asChild size="lg">
                                        <Link href={route('registration.create')}>Fazer inscrição</Link>
                                    </Button>
                                )}

                                {auth.user && !evento.inscricoes_abertas && (
                                    <Button asChild size="lg">
                                        <Link href={route('dashboard')}>Ir para o painel</Link>
                                    </Button>
                                )}
                            </div>

                            {!evento.inscricoes_abertas && evento.situacao === 'published' && (
                                <p className="text-muted-foreground text-sm">As inscrições ainda não abriram ou já encerraram.</p>
                            )}
                        </section>

                        <section aria-labelledby="como-participar" className="flex flex-col gap-8">
                            <h2 id="como-participar" className="text-center text-2xl font-semibold">
                                Como participar
                            </h2>
                            <div className="grid gap-6 sm:grid-cols-3">
                                {passos.map((passo, indice) => (
                                    <div key={passo.titulo} className="bg-card flex flex-col gap-3 rounded-xl border p-5">
                                        <div className="text-muted-foreground flex items-center gap-2 text-xs font-medium tracking-wide uppercase">
                                            <span>Passo {indice + 1}</span>
                                        </div>
                                        <passo.icone className="h-6 w-6 shrink-0" aria-hidden="true" />
                                        <h3 className="font-medium">{passo.titulo}</h3>
                                        <p className="text-muted-foreground text-sm">{passo.texto}</p>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </>
                ) : (
                    <section className="flex flex-col items-center gap-4 pt-24 text-center">
                        <AppLogoIcon className="size-10 fill-current" />
                        <h1 className="text-2xl font-semibold">Nenhum evento publicado no momento</h1>
                        <p className="text-muted-foreground max-w-md">Assim que uma edição do hackathon for aberta, ela aparece aqui.</p>
                    </section>
                )}
            </main>
        </div>
    );
}
