import { Head, Link, usePage } from '@inertiajs/react';
import { CalendarClock, ClipboardList, Rocket, UsersRound } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import ContadorEvento from '@/components/hackathon/contador-evento';
import FundoCircuito from '@/components/hackathon/fundo-circuito';
import LogDeBuild from '@/components/hackathon/log-de-build';
import RodapePublico from '@/components/hackathon/rodape-publico';
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

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-5xl flex-col gap-24 p-4 pb-24 sm:gap-32 sm:p-6">
                {evento ? (
                    <>
                        <FundoCircuito opacidade={0.1} className="flex flex-col items-center gap-10 pt-12 text-center sm:gap-12 sm:pt-24 lg:pt-28">
                            <div>
                                <p
                                    style={{ animationDelay: '0ms' }}
                                    className="text-primary motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:fill-mode-both font-mono text-sm motion-safe:duration-700"
                                >
                                    <span aria-hidden="true">$ </span>
                                    {evento.edicao}ª edição — {evento.situacao_label}
                                </p>
                                <h1
                                    style={{ animationDelay: '80ms' }}
                                    className="font-display motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-4 motion-safe:fill-mode-both mt-3 text-[clamp(2.5rem,6vw,5.5rem)] leading-[1.03] font-semibold tracking-tight text-balance motion-safe:duration-700"
                                >
                                    {evento.nome}
                                </h1>
                                {evento.descricao && (
                                    <p
                                        style={{ animationDelay: '160ms' }}
                                        className="text-muted-foreground motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:fill-mode-both mx-auto mt-5 max-w-2xl text-lg text-balance motion-safe:duration-700"
                                    >
                                        {evento.descricao}
                                    </p>
                                )}
                            </div>

                            <div
                                style={{ animationDelay: '220ms' }}
                                className="motion-safe:animate-in motion-safe:fade-in motion-safe:fill-mode-both motion-safe:duration-700"
                            >
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
                            </div>

                            <div
                                style={{ animationDelay: '300ms' }}
                                className="motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 motion-safe:fill-mode-both flex flex-wrap items-center justify-center gap-3 motion-safe:duration-700"
                            >
                                {!auth.user && (
                                    <Button
                                        asChild
                                        size="lg"
                                        className="shadow-primary/20 hover:shadow-primary/30 shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl"
                                    >
                                        <Link href={route('register')}>Criar conta e participar</Link>
                                    </Button>
                                )}

                                {auth.user && evento.inscricoes_abertas && (
                                    <Button
                                        asChild
                                        size="lg"
                                        className="shadow-primary/20 hover:shadow-primary/30 shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl"
                                    >
                                        <Link href={route('registration.create')}>Fazer inscrição</Link>
                                    </Button>
                                )}

                                {auth.user && !evento.inscricoes_abertas && (
                                    <Button
                                        asChild
                                        size="lg"
                                        className="shadow-primary/20 hover:shadow-primary/30 shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl"
                                    >
                                        <Link href={route('dashboard')}>Ir para o painel</Link>
                                    </Button>
                                )}

                                <Button asChild variant="ghost" size="lg">
                                    <Link href={route('regulamento.show')}>Ver regulamento</Link>
                                </Button>
                            </div>

                            {!evento.inscricoes_abertas && evento.situacao === 'published' && (
                                <p className="text-muted-foreground text-sm">As inscrições ainda não abriram ou já encerraram.</p>
                            )}

                            <div
                                style={{ animationDelay: '160ms' }}
                                className="motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-4 motion-safe:fill-mode-both mx-auto w-full max-w-lg motion-safe:duration-1000"
                            >
                                <LogDeBuild />
                            </div>
                        </FundoCircuito>

                        <section aria-labelledby="como-participar" className="flex flex-col gap-10">
                            <h2 id="como-participar" className="font-display text-center text-2xl font-semibold tracking-tight sm:text-3xl">
                                <span aria-hidden="true">
                                    <span className="text-muted-foreground font-mono text-lg font-normal">$ </span>
                                    como_participar
                                </span>
                                <span className="sr-only">Como participar</span>
                            </h2>
                            <div className="grid gap-6 sm:grid-cols-3">
                                {passos.map((passo, indice) => (
                                    <div
                                        key={passo.titulo}
                                        style={{ animationDelay: `${indice * 120}ms` }}
                                        className="bg-card motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-3 motion-safe:fill-mode-both group hover:border-primary/30 flex flex-col gap-3.5 rounded-xl border p-6 shadow-sm transition-all hover:-translate-y-1 hover:shadow-md motion-safe:duration-700"
                                    >
                                        <div className="text-primary font-mono text-xs">[passo {indice + 1}]</div>
                                        <span className="bg-primary/10 text-primary group-hover:bg-primary/15 flex size-11 items-center justify-center rounded-lg transition-colors">
                                            <passo.icone className="h-5 w-5 shrink-0" aria-hidden="true" />
                                        </span>
                                        <h3 className="font-display font-medium">{passo.titulo}</h3>
                                        <p className="text-muted-foreground text-sm leading-relaxed">{passo.texto}</p>
                                    </div>
                                ))}
                            </div>
                        </section>
                    </>
                ) : (
                    <section className="flex flex-col items-center gap-4 pt-24 text-center">
                        <span className="bg-primary text-primary-foreground flex size-14 items-center justify-center rounded-xl">
                            <AppLogoIcon className="size-8 fill-current" />
                        </span>
                        <h1 className="font-display text-2xl font-semibold tracking-tight">Nenhum evento publicado no momento</h1>
                        <p className="text-muted-foreground max-w-md">Assim que uma edição do hackathon for aberta, ela aparece aqui.</p>
                    </section>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
