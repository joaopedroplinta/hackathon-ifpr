import { Head, Link, router } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CalendarDays, LoaderCircle, MapPin, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { LinhaItemAgenda } from '@/types/agenda-admin';

interface Props {
    itens: LinhaItemAgenda[];
}

function formatarHorario(inicioIso: string, fimIso: string): string {
    const opcoes: Intl.DateTimeFormatOptions = { timeZone: 'America/Sao_Paulo', dateStyle: 'short', timeStyle: 'short' };
    const inicio = new Date(inicioIso).toLocaleString('pt-BR', opcoes);
    const fim = new Date(fimIso).toLocaleTimeString('pt-BR', { timeZone: 'America/Sao_Paulo', hour: '2-digit', minute: '2-digit' });

    return `${inicio}–${fim}`;
}

export default function ListaAgenda({ itens }: Props) {
    // Duplo clique não pode disparar a ação duas vezes -- em "despublicar"
    // isso alternaria de volta pra "publicado" sem querer.
    const [emAndamento, setEmAndamento] = useState<number | null>(null);

    const alternarPublicacao = (item: LinhaItemAgenda) => {
        setEmAndamento(item.id);
        router.patch(route('admin.agenda.publish', item.id), {}, { preserveScroll: true, onFinish: () => setEmAndamento(null) });
    };

    const remover = (item: LinhaItemAgenda) => {
        setEmAndamento(item.id);
        router.delete(route('admin.agenda.destroy', item.id), { preserveScroll: true, onFinish: () => setEmAndamento(null) });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Agenda', href: route('admin.agenda.index') }]}>
            <Head title="Agenda" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-4xl p-4 sm:p-6">
                <header className="mb-6 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-medium tracking-tight">Agenda</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {itens.length === 1 ? '1 item' : `${itens.length} itens`} — só o que estiver publicado aparece pro público.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={route('admin.agenda.create')}>Novo item</Link>
                    </Button>
                </header>

                {itens.length === 0 ? (
                    <div className="bg-card flex flex-col items-center gap-3 rounded-2xl p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <CalendarDays className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-medium">Nenhum item na agenda</p>
                        <p className="text-muted-foreground text-sm">Crie o primeiro item para começar a montar a programação.</p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {itens.map((item) => (
                            <li key={item.id} className="bg-card rounded-2xl p-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span
                                                className={`inline-block rounded-full px-2 py-0.5 text-xs ${
                                                    item.publicado
                                                        ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'
                                                        : 'bg-muted text-muted-foreground'
                                                }`}
                                            >
                                                {item.publicado ? 'Publicado' : 'Rascunho'}
                                            </span>
                                            <span className="text-muted-foreground text-xs">{item.tipo_label}</span>
                                            {item.trilha && (
                                                <span
                                                    className="inline-flex items-center gap-1.5 text-xs"
                                                    style={{ color: item.trilha.cor ?? undefined }}
                                                >
                                                    <span
                                                        className="h-2 w-2 rounded-full"
                                                        style={{ backgroundColor: item.trilha.cor ?? 'currentColor' }}
                                                        aria-hidden="true"
                                                    />
                                                    {item.trilha.nome}
                                                </span>
                                            )}
                                        </div>
                                        <p className="mt-1 font-medium">{item.titulo}</p>
                                        <div className="text-muted-foreground mt-1 flex flex-wrap gap-3 text-xs">
                                            <span>{formatarHorario(item.inicia_em, item.termina_em)}</span>
                                            {item.local && (
                                                <span className="flex items-center gap-1">
                                                    <MapPin className="h-3 w-3 shrink-0" aria-hidden="true" />
                                                    {item.local}
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-1">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={emAndamento === item.id}
                                            onClick={() => alternarPublicacao(item)}
                                        >
                                            {emAndamento === item.id && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                            {item.publicado ? 'Despublicar' : 'Publicar'}
                                        </Button>
                                        <Button asChild variant="ghost" size="icon" aria-label={`Editar ${item.titulo}`}>
                                            <Link href={route('admin.agenda.edit', item.id)}>
                                                <Pencil className="h-4 w-4" aria-hidden="true" />
                                            </Link>
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remover ${item.titulo}`}
                                            disabled={emAndamento === item.id}
                                            onClick={() => remover(item)}
                                            className="hover:text-destructive"
                                        >
                                            <Trash2 className="h-4 w-4" aria-hidden="true" />
                                        </Button>
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </motion.div>
        </AppLayout>
    );
}
