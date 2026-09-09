import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight, CalendarDays, Check, ClipboardList, Rocket, UsersRound } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import ContadorEvento from '@/components/hackathon/contador-evento';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { EstatisticasEvento, EventoPublico } from '@/types/publico';

type Props = { evento: EventoPublico | null; estatisticas: EstatisticasEvento | null };

const steps = [
    {
        icon: UsersRound,
        title: 'Encontre sua equipe.',
        text: 'Reúna suas ideias com as de outras pessoas. Crie uma equipe ou entre pelo código de convite.',
    },
    {
        icon: ClipboardList,
        title: 'Tire a ideia do papel.',
        text: 'Acompanhe as oficinas, desenvolva a solução e salve o progresso do projeto durante o evento.',
    },
    {
        icon: Rocket,
        title: 'Mostre o que vocês criaram.',
        text: 'Envie a descrição e o repositório até o prazo — o vídeo é opcional. Cada entrega fica no histórico da equipe.',
    },
];

export default function Inicio({ evento, estatisticas }: Props) {
    const { auth, evento: currentEvent } = usePage<SharedData>().props;
    const registered = currentEvent?.inscrito;
    const action = auth.user
        ? evento?.inscricoes_abertas && !registered
            ? { href: 'registration.create', label: 'Fazer minha inscrição' }
            : { href: 'dashboard', label: 'Acessar meu painel' }
        : evento?.inscricoes_abertas
          ? { href: 'register', label: 'Quero participar' }
          : { href: 'agenda.index', label: 'Explorar a programação' };
    const startDate = evento?.inicia_em
        ? new Intl.DateTimeFormat('pt-BR', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'America/Sao_Paulo' }).format(
              new Date(evento.inicia_em),
          )
        : null;

    return (
        <div className="public-experience bg-background text-foreground min-h-svh">
            <Head title="Início" />
            <CabecalhoPublico />
            <main id="conteudo-publico" tabIndex={-1} className="mx-auto w-full max-w-7xl scroll-mt-24 px-4 pb-20 outline-none sm:px-6 lg:px-8">
                {evento ? (
                    <>
                        <section
                            className="grid items-center gap-10 py-10 sm:py-16 lg:grid-cols-[1.1fr_1fr] lg:gap-12 lg:py-20"
                            aria-labelledby="event-title"
                        >
                            <div>
                                <div className="mb-7 flex flex-wrap items-center gap-3 text-xs font-semibold">
                                    <span className="bg-primary/10 text-primary rounded-full px-3 py-1.5">{evento.edicao}ª edição</span>
                                    <span className="text-muted-foreground">{evento.situacao_label}</span>
                                </div>
                                <h1
                                    id="event-title"
                                    className="max-w-2xl text-[clamp(2.6rem,5.8vw,5.4rem)] leading-[1.02] font-semibold tracking-[-0.065em] text-balance"
                                >
                                    {evento.nome}
                                </h1>
                                <p className="text-foreground mt-6 text-xl font-medium tracking-tight sm:text-2xl">
                                    Boas ideias começam com um encontro.
                                </p>
                                <p className="text-muted-foreground mt-5 max-w-lg text-base leading-relaxed">
                                    {evento.descricao ||
                                        'Conecte-se com outras pessoas, explore novos desafios e transforme uma ideia em um projeto com a sua equipe.'}
                                </p>
                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    <Button asChild size="lg" className="h-12 gap-3 rounded-full px-6">
                                        <Link href={route(action.href)}>
                                            {action.label}
                                            <ArrowUpRight className="size-4" aria-hidden="true" />
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline" size="lg" className="h-12 rounded-full px-6">
                                        <Link href={route('regulamento.show')}>Como funciona</Link>
                                    </Button>
                                </div>
                                <p className="text-muted-foreground mt-5 flex items-start gap-2 text-sm">
                                    <Check className="text-primary mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    {registered
                                        ? 'Sua inscrição está confirmada. Acompanhe os próximos passos no painel.'
                                        : evento.inscricoes_abertas
                                          ? 'Inscrições abertas. Crie sua conta para começar.'
                                          : evento.situacao === 'running'
                                            ? 'O hackathon está acontecendo. Acompanhe a agenda.'
                                            : evento.situacao === 'finished'
                                              ? 'Esta edição terminou. Consulte a página de resultados.'
                                              : 'As inscrições não estão abertas no momento.'}
                                </p>
                            </div>

                            <aside
                                className="event-art relative flex min-h-[390px] flex-col justify-between overflow-hidden rounded-[2rem] p-7 sm:min-h-[460px] sm:p-10"
                                aria-label="Sobre o encontro"
                            >
                                <div className="flex items-center justify-between gap-4">
                                    <span className="text-xs font-medium tracking-[0.18em] uppercase">Ideias que se encontram</span>
                                    <AppLogoIcon className="size-9 shrink-0 fill-current text-[#d6ecac]" />
                                </div>
                                <div className="py-10">
                                    <p className="text-[clamp(3rem,5vw,4.5rem)] leading-[0.98] font-bold tracking-[-0.05em]">
                                        Conectar.
                                        <br />
                                        <span className="text-[#d6ecac]">Criar.</span>
                                        <br />
                                        Transformar.
                                    </p>
                                    <p className="mt-6 max-w-64 text-sm leading-relaxed text-white/75">
                                        Tecnologia ganha sentido quando a gente constrói junto.
                                    </p>
                                </div>
                                <div className="flex items-center gap-3 border-t border-white/20 pt-5">
                                    <CalendarDays className="size-5 shrink-0 text-[#d6ecac]" aria-hidden="true" />
                                    <div className="text-sm">
                                        <p className="font-semibold">{startDate || 'Acompanhe a programação'}</p>
                                        <p className="mt-1 text-white/70">IFPR · Campus Pinhais</p>
                                    </div>
                                    <span className="ml-auto text-3xl font-light text-white/50" aria-hidden="true">
                                        {String(evento.edicao).padStart(2, '0')}
                                    </span>
                                </div>
                            </aside>
                        </section>

                        {estatisticas && (
                            <section
                                aria-label="O evento em números"
                                className="bg-secondary/60 grid grid-cols-2 gap-6 rounded-2xl px-6 py-7 sm:grid-cols-3 sm:gap-10 sm:px-8"
                            >
                                {[
                                    { value: estatisticas.inscritos, label: 'pessoas inscritas' },
                                    { value: estatisticas.equipes, label: 'equipes formadas' },
                                    ...(estatisticas.trilhas > 0 ? [{ value: estatisticas.trilhas, label: 'trilhas para explorar' }] : []),
                                ].map((stat) => (
                                    <div key={stat.label} className="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-3">
                                        <span className="text-3xl font-semibold tracking-tight tabular-nums">{stat.value}</span>
                                        <span className="text-muted-foreground text-sm">{stat.label}</span>
                                    </div>
                                ))}
                            </section>
                        )}

                        <section aria-labelledby="como-participar" className="py-16 sm:py-20">
                            <div className="mb-9 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                                <div>
                                    <p className="text-muted-foreground mb-3 text-sm">Da primeira ideia à entrega</p>
                                    <h2 id="como-participar" className="text-3xl font-bold tracking-tight sm:text-4xl">
                                        Seu próximo desafio começa aqui.
                                    </h2>
                                </div>
                                <Link
                                    href={route('rubrica.show')}
                                    className="text-primary inline-flex min-h-11 shrink-0 items-center gap-2 text-sm font-semibold"
                                >
                                    Conheça os critérios
                                    <ArrowRight className="size-4" aria-hidden="true" />
                                </Link>
                            </div>
                            <div className="grid gap-4 md:grid-cols-3">
                                {steps.map((step, index) => (
                                    <article key={step.title} className="public-step border-border border-t-2 p-5 sm:p-7">
                                        <div className="mb-8 flex items-center justify-between">
                                            <span className="bg-primary/10 text-primary flex size-11 items-center justify-center rounded-xl">
                                                <step.icon className="size-5" aria-hidden="true" />
                                            </span>
                                            <span className="text-muted-foreground font-mono text-xs">0{index + 1}</span>
                                        </div>
                                        <h3 className="text-lg font-semibold tracking-tight">{step.title}</h3>
                                        <p className="text-muted-foreground mt-3 text-sm leading-relaxed">{step.text}</p>
                                    </article>
                                ))}
                            </div>
                        </section>

                        <section className="border-border bg-card flex flex-col items-center justify-between gap-8 rounded-[2rem] border p-7 sm:p-12 lg:flex-row">
                            <div className="max-w-md text-center lg:text-left">
                                <h2 className="text-2xl font-bold tracking-tight">Faça parte desse encontro.</h2>
                                <p className="text-muted-foreground mt-2 text-sm leading-relaxed">
                                    Confira os horários e planeje sua participação nas atividades do hackathon.
                                </p>
                                <Link
                                    href={route('agenda.index')}
                                    className="text-primary mt-4 inline-flex min-h-11 items-center gap-2 text-sm font-semibold"
                                >
                                    Ver a agenda completa
                                    <ArrowRight className="size-4" aria-hidden="true" />
                                </Link>
                            </div>
                            {evento.inicia_em && evento.situacao === 'published' && (
                                <ContadorEvento alvo={evento.inicia_em} rotulo="Faltam para o início" />
                            )}
                            {evento.situacao === 'finished' && (
                                <Button asChild variant="outline" className="h-12">
                                    <Link href={route('resultados.show')}>Consultar resultados</Link>
                                </Button>
                            )}
                        </section>
                    </>
                ) : (
                    <section className="flex min-h-[60vh] flex-col items-center justify-center gap-5 py-16 text-center">
                        <span className="bg-primary/10 text-primary flex size-16 items-center justify-center rounded-2xl">
                            <AppLogoIcon className="size-9 fill-current" />
                        </span>
                        <h1 className="max-w-xl text-3xl font-bold tracking-tight sm:text-4xl">O próximo encontro começa com uma ideia.</h1>
                        <p className="text-muted-foreground max-w-md">
                            Nenhum evento publicado no momento. Assim que uma edição do hackathon for aberta, ela aparece aqui.
                        </p>
                        <Button asChild variant="outline" className="h-12">
                            <Link href={route('edicoes.index')}>
                                Explorar edições anteriores
                                <ArrowRight className="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                    </section>
                )}
            </main>
            <RodapePublico />
        </div>
    );
}
