import { Head, Link, usePage } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CalendarClock, ClipboardList, Rocket, UsersRound } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import ContadorEvento from '@/components/hackathon/contador-evento';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { EstatisticasEvento, EventoPublico } from '@/types/publico';

interface Props {
    evento: EventoPublico | null;
    estatisticas: EstatisticasEvento | null;
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

export default function Inicio({ evento, estatisticas }: Props) {
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

            <main className="mx-auto flex w-full max-w-5xl flex-col gap-16 p-4 pb-24 sm:p-6">
                {evento ? (
                    <>
                        {/* Sem card, sem borda -- inspirado no hero do Raycast: título
                            grande e centralizado, muito espaço vazio ao redor, zero
                            ilustração competindo com o texto. A única textura é a grade
                            de pontos + brilho radial atrás de tudo (mesmo truque do
                            painel de login), pra não ficar chapado. */}
                        <motion.section initial="oculto" animate="visivel" variants={subir} className="relative overflow-hidden py-12 sm:py-20">
                            {/* Textura de fundo -- pintada antes do conteúdo no DOM, mas
                                como é `absolute` ela flutuaria por cima de qualquer irmão
                                não posicionado (ordem de pintura do CSS, não do DOM). Por
                                isso todo o conteúdo real vai dentro do wrapper `relative`
                                logo abaixo -- mesma pegadinha que já resolvemos no hero 3D
                                e no painel de login. */}
                            <div
                                className="pointer-events-none absolute inset-0"
                                aria-hidden="true"
                                style={{
                                    backgroundImage: 'radial-gradient(circle, var(--border) 1px, transparent 1px)',
                                    backgroundSize: '28px 28px',
                                    maskImage: 'radial-gradient(ellipse 60% 70% at 50% 20%, black 30%, transparent 80%)',
                                    WebkitMaskImage: 'radial-gradient(ellipse 60% 70% at 50% 20%, black 30%, transparent 80%)',
                                }}
                            />
                            <div
                                className="bg-verde-brilho pointer-events-none absolute top-0 left-1/2 size-96 -translate-x-1/2 -translate-y-1/3 rounded-full opacity-[0.1] blur-[110px]"
                                aria-hidden="true"
                            />

                            <div className="relative flex flex-col items-center gap-8 text-center">
                                <p className="text-primary font-mono text-sm font-semibold tracking-wide uppercase">
                                    {evento.edicao}ª edição · {evento.situacao_label}
                                </p>

                                <div>
                                    <h1 className="mx-auto max-w-3xl text-[clamp(2.25rem,5.5vw,4rem)] leading-[1.05] font-bold tracking-tight text-balance">
                                        {evento.nome}
                                    </h1>
                                    {evento.descricao && (
                                        <p className="text-muted-foreground mx-auto mt-5 max-w-xl text-base leading-relaxed text-balance sm:text-lg">
                                            {evento.descricao}
                                        </p>
                                    )}
                                </div>

                                {evento.situacao === 'running' ? (
                                    <p role="status" className="flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400">
                                        <CalendarClock className="h-4 w-4 shrink-0" aria-hidden="true" />O evento está acontecendo agora.
                                    </p>
                                ) : evento.situacao === 'finished' ? (
                                    <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm font-medium">
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

                                    <Button asChild variant="outline" size="lg">
                                        <Link href={route('regulamento.show')}>Ver regulamento</Link>
                                    </Button>
                                </div>

                                {!evento.inscricoes_abertas && evento.situacao === 'published' && (
                                    <p className="text-muted-foreground text-sm">As inscrições ainda não abriram ou já encerraram.</p>
                                )}

                                {estatisticas && (
                                    <div className="border-border flex flex-wrap items-center justify-center gap-x-10 gap-y-4 border-t pt-8">
                                        <div className="flex flex-col items-center gap-1">
                                            <span className="text-2xl font-bold tabular-nums">{estatisticas.inscritos}</span>
                                            <span className="text-muted-foreground text-xs tracking-wide uppercase">inscritos</span>
                                        </div>
                                        <div className="flex flex-col items-center gap-1">
                                            <span className="text-2xl font-bold tabular-nums">{estatisticas.equipes}</span>
                                            <span className="text-muted-foreground text-xs tracking-wide uppercase">equipes formadas</span>
                                        </div>
                                        {estatisticas.trilhas > 0 && (
                                            <div className="flex flex-col items-center gap-1">
                                                <span className="text-2xl font-bold tabular-nums">{estatisticas.trilhas}</span>
                                                <span className="text-muted-foreground text-xs tracking-wide uppercase">trilhas</span>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </motion.section>

                        <section aria-labelledby="como-participar" className="flex flex-col gap-8">
                            <div className="text-center">
                                <p className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">3 passos</p>
                                <h2 id="como-participar" className="mt-1 text-2xl font-bold tracking-tight">
                                    Como participar
                                </h2>
                            </div>
                            <div className="border-border grid divide-y overflow-hidden rounded-xl border sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                                {passos.map((passo, indice) => (
                                    <motion.div
                                        key={passo.titulo}
                                        whileHover={reduzMovimento ? undefined : { backgroundColor: 'var(--muted)' }}
                                        className="bg-card flex flex-col gap-3 p-6"
                                    >
                                        <div className="flex items-center gap-3">
                                            <span className="border-border text-muted-foreground flex size-7 shrink-0 items-center justify-center rounded-md border text-xs font-bold tabular-nums">
                                                {String(indice + 1).padStart(2, '0')}
                                            </span>
                                            <passo.icone className="text-primary h-5 w-5 shrink-0" aria-hidden="true" />
                                        </div>
                                        <h3 className="font-semibold">{passo.titulo}</h3>
                                        <p className="text-muted-foreground text-sm leading-relaxed">{passo.texto}</p>
                                    </motion.div>
                                ))}
                            </div>
                        </section>
                    </>
                ) : (
                    <section className="border-border bg-card flex flex-col items-center gap-4 rounded-xl border p-10 py-24 text-center sm:p-16">
                        <span className="bg-primary text-primary-foreground flex size-14 items-center justify-center rounded-xl">
                            <AppLogoIcon className="size-8 fill-current" />
                        </span>
                        <h1 className="text-2xl font-bold tracking-tight">Nenhum evento publicado no momento</h1>
                        <p className="text-muted-foreground max-w-md">Assim que uma edição do hackathon for aberta, ela aparece aqui.</p>
                    </section>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
