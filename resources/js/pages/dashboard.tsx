import { Head, Link, usePage } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { ArrowRight, Award, Calendar, Check, ChevronRight, ClipboardCheck, FileWarning, LayoutGrid, Lock, QrCode } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type PassoTrilha, type PerfilCertificado } from '@/types/dashboard';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Início', href: '/dashboard' }];

interface Props {
    trilha: PassoTrilha[] | null;
    perfil_certificado: PerfilCertificado;
}

function ItemTrilha({ passo, indice, total, reduzMovimento }: { passo: PassoTrilha; indice: number; total: number; reduzMovimento: boolean | null }) {
    const concluido = passo.status === 'concluido';
    const bloqueado = passo.status === 'bloqueado';
    const ultimo = indice === total - 1;

    const marcador = (
        <span
            className={`flex size-9 shrink-0 items-center justify-center rounded-full ${
                concluido ? 'bg-primary text-primary-foreground' : bloqueado ? 'bg-muted text-muted-foreground' : 'bg-muted text-foreground'
            }`}
        >
            {concluido ? (
                <Check className="size-4" aria-hidden="true" />
            ) : bloqueado ? (
                <Lock className="size-3.5" aria-hidden="true" />
            ) : (
                <span className="text-sm font-medium">{indice + 1}</span>
            )}
        </span>
    );

    const conteudo = (
        <div className="flex gap-4">
            <div className="flex flex-col items-center">
                {marcador}
                {!ultimo && <span className="bg-border mt-1 w-px flex-1" aria-hidden="true" />}
            </div>

            <div className={`flex-1 items-start justify-between gap-3 sm:flex ${ultimo ? 'pb-1' : 'pb-8'}`}>
                <div>
                    <p className={`font-semibold ${bloqueado ? 'text-muted-foreground' : ''}`}>{passo.titulo}</p>
                    <p className="text-muted-foreground mt-1 text-sm leading-relaxed">{passo.descricao}</p>
                </div>

                {passo.href && <ChevronRight className="text-muted-foreground mt-1 hidden size-4 shrink-0 sm:block" aria-hidden="true" />}
            </div>
        </div>
    );

    if (!passo.href) {
        return <li>{conteudo}</li>;
    }

    return (
        <motion.li whileHover={reduzMovimento ? undefined : { x: 2 }} transition={{ type: 'spring', stiffness: 400, damping: 25 }}>
            <Link href={passo.href} className="focus-visible:ring-ring -m-2 block rounded-lg p-2 focus-visible:ring-2 focus-visible:outline-none">
                {conteudo}
            </Link>
        </motion.li>
    );
}

export default function Dashboard({ trilha, perfil_certificado: perfilCertificado }: Props) {
    const { auth, evento } = usePage<SharedData>().props;
    const reduzMovimento = useReducedMotion();
    const completed = trilha?.filter((step) => step.status === 'concluido').length ?? 0;
    const nextStep = trilha?.find((step) => step.status === 'disponivel' && step.href && step.chave !== 'credencial');
    const shortcuts = [
        { title: 'Meu crachá', description: 'QR Code para o check-in.', href: 'credencial.show', icon: QrCode },
        { title: 'Programação', description: 'Horários e atividades do evento.', href: 'agenda.index', icon: Calendar },
        { title: 'Certificados', description: 'Consulte suas participações.', href: 'certificates.index', icon: Award },
    ];

    const primeiroNome = auth.user.name.split(' ')[0];

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Início" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-8">
                <header className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <p className="text-primary mb-2 text-xs font-semibold tracking-widest uppercase">Seu espaço no hackathon</p>
                        <h1 className="text-3xl font-bold tracking-tight">Olá, {primeiroNome}.</h1>
                        <p className="text-muted-foreground mt-2 text-sm">{evento?.nome ?? 'Tudo para acompanhar sua participação.'}</p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {auth.is_staff && (
                            <Button asChild variant="outline" className="h-11">
                                <Link href={route('painel.dashboard')}>
                                    <LayoutGrid className="size-4" aria-hidden="true" />
                                    Organizar evento
                                </Link>
                            </Button>
                        )}
                        {auth.is_judge && (
                            <Button asChild variant="outline" className="h-11">
                                <Link href={route('jurado.index')}>
                                    <ClipboardCheck className="size-4" aria-hidden="true" />
                                    Minhas avaliações
                                </Link>
                            </Button>
                        )}
                    </div>
                </header>

                {perfilCertificado.pendente && (
                    <section
                        className="flex flex-col gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"
                        aria-labelledby="perfil-certificado-titulo"
                    >
                        <div className="flex gap-3">
                            <FileWarning className="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-400" aria-hidden="true" />
                            <div>
                                <h2 id="perfil-certificado-titulo" className="font-semibold">
                                    Complete seu perfil para o certificado
                                </h2>
                                <p className="text-muted-foreground mt-1 text-sm leading-relaxed">
                                    Faltam {perfilCertificado.campos.join(' e ')}. Essas informações aparecem no certificado e ajudam a garantir sua
                                    validade.
                                </p>
                            </div>
                        </div>
                        <Button asChild variant="outline" className="bg-background h-11 shrink-0 border-amber-600/40 hover:bg-amber-500/10">
                            <Link href={route('profile.edit')}>Completar perfil</Link>
                        </Button>
                    </section>
                )}

                {trilha ? (
                    <div className="grid items-start gap-6 lg:grid-cols-[1.35fr_1fr]">
                        <section className="border-border bg-card rounded-2xl border p-6 sm:p-8" aria-labelledby="participation-title">
                            <div className="border-border mb-7 flex items-center justify-between gap-3 border-b pb-5">
                                <h2 id="participation-title" className="text-lg font-semibold tracking-tight">
                                    Sua jornada
                                </h2>
                                <span className="text-muted-foreground text-xs">{completed} etapas concluídas</span>
                            </div>
                            <ol className="flex flex-col">
                                {trilha.map((passo, indice) => (
                                    <ItemTrilha
                                        key={passo.chave}
                                        passo={passo}
                                        indice={indice}
                                        total={trilha.length}
                                        reduzMovimento={reduzMovimento}
                                    />
                                ))}
                            </ol>
                        </section>
                        <aside className="flex flex-col gap-5">
                            <section className="event-art relative overflow-hidden rounded-2xl p-6 sm:p-8">
                                <p className="text-xs font-semibold tracking-widest text-[#d6ecac] uppercase">
                                    {nextStep ? 'Seu próximo passo' : 'Acompanhe o evento'}
                                </p>
                                <h2 className="mt-4 text-2xl font-semibold tracking-tight">{nextStep?.titulo ?? 'Tudo no seu tempo.'}</h2>
                                <p className="mt-3 text-sm leading-relaxed text-white/80">
                                    {nextStep?.descricao ?? 'Confira sua jornada ao lado e acompanhe os horários das atividades na agenda.'}
                                </p>
                                <Button asChild className="mt-6 h-11 bg-[#d6ecac] text-[#183b2b] hover:bg-[#e4f3c9]">
                                    <Link href={nextStep?.href ?? route('agenda.index')}>
                                        {nextStep ? 'Continuar' : 'Ver programação'}
                                        <ArrowRight className="size-4" aria-hidden="true" />
                                    </Link>
                                </Button>
                            </section>
                            <section aria-label="Acesso rápido" className="border-border bg-card divide-y rounded-2xl border px-5">
                                {shortcuts.map((item) => (
                                    <Link key={item.href} href={route(item.href)} className="group flex items-center gap-4 py-5">
                                        <item.icon className="text-primary size-5 shrink-0" aria-hidden="true" />
                                        <div className="flex-1">
                                            <p className="text-sm font-semibold">{item.title}</p>
                                            <p className="text-muted-foreground mt-1 text-xs">{item.description}</p>
                                        </div>
                                        <ChevronRight className="text-muted-foreground group-hover:text-primary size-4" aria-hidden="true" />
                                    </Link>
                                ))}
                            </section>
                        </aside>
                    </div>
                ) : (
                    <section className="border-border bg-card flex max-w-xl flex-col items-center gap-3 rounded-xl border p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Calendar className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhum evento aberto</p>
                        <p className="text-muted-foreground text-sm">Assim que a organização publicar o próximo hackathon, ele aparece aqui.</p>
                    </section>
                )}
            </motion.div>
        </AppLayout>
    );
}
