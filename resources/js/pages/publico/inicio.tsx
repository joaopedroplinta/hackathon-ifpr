import { Head, Link, usePage } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CalendarClock, ClipboardList, Rocket, UsersRound } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import ContadorEvento from '@/components/hackathon/contador-evento';
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
    const reduzMovimento = useReducedMotion();

    const subir: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 14 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.6, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Início" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-5xl flex-col gap-24 p-4 pb-24 sm:gap-32 sm:p-6">
                {evento ? (
                    <>
                        <motion.section
                            initial="oculto"
                            animate="visivel"
                            variants={subir}
                            className="flex flex-col items-center gap-10 pt-16 text-center sm:gap-12 sm:pt-28 lg:pt-32"
                        >
                            <div>
                                <p className="text-muted-foreground text-sm tracking-wide uppercase">
                                    {evento.edicao}ª edição · {evento.situacao_label}
                                </p>
                                <h1 className="mt-4 text-[clamp(2.5rem,6vw,5.5rem)] leading-[1.05] font-medium tracking-tight text-balance">
                                    {evento.nome}
                                </h1>
                                {evento.descricao && (
                                    <p className="text-muted-foreground mx-auto mt-5 max-w-2xl text-lg text-balance">{evento.descricao}</p>
                                )}
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

                                <Button asChild variant="ghost" size="lg">
                                    <Link href={route('regulamento.show')}>Ver regulamento</Link>
                                </Button>
                            </div>

                            {!evento.inscricoes_abertas && evento.situacao === 'published' && (
                                <p className="text-muted-foreground text-sm">As inscrições ainda não abriram ou já encerraram.</p>
                            )}
                        </motion.section>

                        <section aria-labelledby="como-participar" className="flex flex-col gap-12">
                            <h2 id="como-participar" className="text-center text-2xl font-medium tracking-tight sm:text-3xl">
                                Como participar
                            </h2>
                            <div className="grid gap-6 sm:grid-cols-3">
                                {passos.map((passo) => (
                                    <motion.div
                                        key={passo.titulo}
                                        whileHover={reduzMovimento ? undefined : { y: -2 }}
                                        transition={{ type: 'spring', stiffness: 400, damping: 25 }}
                                        className="bg-card flex flex-col gap-3.5 rounded-2xl p-6"
                                    >
                                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                                            <passo.icone className="h-5 w-5 shrink-0" aria-hidden="true" />
                                        </span>
                                        <h3 className="font-medium">{passo.titulo}</h3>
                                        <p className="text-muted-foreground text-sm leading-relaxed">{passo.texto}</p>
                                    </motion.div>
                                ))}
                            </div>
                        </section>
                    </>
                ) : (
                    <section className="flex flex-col items-center gap-4 pt-24 text-center">
                        <span className="bg-primary text-primary-foreground flex size-14 items-center justify-center rounded-2xl">
                            <AppLogoIcon className="size-8 fill-current" />
                        </span>
                        <h1 className="text-2xl font-medium tracking-tight">Nenhum evento publicado no momento</h1>
                        <p className="text-muted-foreground max-w-md">Assim que uma edição do hackathon for aberta, ela aparece aqui.</p>
                    </section>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
