import { Head, Link, router, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { ClipboardList, LoaderCircle, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import ResumoErro from '@/components/hackathon/resumo-erro';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { LinhaRubrica } from '@/types/rubrica';

interface Props {
    rubricas: LinhaRubrica[];
}

export default function ListaRubricas({ rubricas }: Props) {
    const [emAndamento, setEmAndamento] = useState<number | null>(null);
    const [rubricaParaRemover, setRubricaParaRemover] = useState<LinhaRubrica | null>(null);
    const { data, setData, post, processing, errors, reset } = useForm({ name: '' });

    const criar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('painel.rubrica.store'), { onSuccess: () => reset() });
    };

    const ativar = (rubrica: LinhaRubrica) => {
        setEmAndamento(rubrica.id);
        router.patch(route('painel.rubrica.activate', rubrica.id), {}, { preserveScroll: true, onFinish: () => setEmAndamento(null) });
    };

    const remover = (rubrica: LinhaRubrica) => {
        setEmAndamento(rubrica.id);
        router.delete(route('painel.rubrica.destroy', rubrica.id), {
            preserveScroll: true,
            onSuccess: () => setRubricaParaRemover(null),
            onFinish: () => setEmAndamento(null),
        });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Rubrica', href: route('painel.rubrica.index') }]}>
            <Head title="Rubrica" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-3xl p-4 sm:p-6 lg:p-8">
                <header className="border-border/70 mb-8 border-b pb-6">
                    <h1 className="text-3xl font-medium tracking-[-0.03em]">Rubrica</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Só a rubrica ativa conta pro cálculo e aparece pro jurado e pro público.</p>
                </header>

                <form onSubmit={criar} className="border-border/70 bg-card/80 mb-8 grid gap-3 rounded-3xl border p-5 shadow-sm" noValidate>
                    <ResumoErro erros={errors} />
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="flex-1">
                            <Label htmlFor="name">Nova rubrica</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Ex.: Rubrica 2026"
                                aria-describedby={errors.name ? 'name-erro' : undefined}
                            />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            Criar
                        </Button>
                    </div>
                </form>

                {rubricas.length === 0 ? (
                    <div className="border-border/70 bg-card/80 flex flex-col items-center gap-3 rounded-3xl border p-10 text-center shadow-sm">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <ClipboardList className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhuma rubrica cadastrada</p>
                        <p className="text-muted-foreground text-sm">Crie a primeira acima pra começar a montar os critérios.</p>
                    </div>
                ) : (
                    <ul className="border-border/70 bg-card/80 flex flex-col divide-y overflow-hidden rounded-3xl border shadow-sm">
                        {rubricas.map((rubrica) => (
                            <li key={rubrica.id} className="p-4">
                                <div className="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span
                                                className={`inline-block rounded-full px-2 py-0.5 text-xs ${
                                                    rubrica.ativa
                                                        ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400'
                                                        : 'bg-muted text-muted-foreground'
                                                }`}
                                            >
                                                {rubrica.ativa ? 'Ativa' : 'Inativa'}
                                            </span>
                                        </div>
                                        <Link href={route('painel.rubrica.show', rubrica.id)} className="mt-1 block font-semibold hover:underline">
                                            {rubrica.nome}
                                        </Link>
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            {rubrica.total_criterios === 1 ? '1 critério' : `${rubrica.total_criterios} critérios`} · soma dos pesos:{' '}
                                            {rubrica.soma_pesos}
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 items-center gap-1">
                                        {!rubrica.ativa && (
                                            <Button variant="outline" size="sm" disabled={emAndamento === rubrica.id} onClick={() => ativar(rubrica)}>
                                                {emAndamento === rubrica.id && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                                Ativar
                                            </Button>
                                        )}
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            aria-label={`Remover ${rubrica.nome}`}
                                            disabled={emAndamento === rubrica.id}
                                            onClick={() => setRubricaParaRemover(rubrica)}
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

                <Dialog open={rubricaParaRemover !== null} onOpenChange={(aberto) => !aberto && setRubricaParaRemover(null)}>
                    <DialogContent className="rounded-3xl sm:max-w-lg">
                        <DialogTitle>Excluir rubrica?</DialogTitle>
                        <DialogDescription>
                            {rubricaParaRemover
                                ? `A rubrica “${rubricaParaRemover.nome}” e seus critérios serão removidos. Esta ação não pode ser desfeita.`
                                : ''}
                        </DialogDescription>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="secondary">Cancelar</Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                disabled={rubricaParaRemover === null || emAndamento === rubricaParaRemover?.id}
                                onClick={() => {
                                    if (rubricaParaRemover) remover(rubricaParaRemover);
                                }}
                            >
                                {emAndamento === rubricaParaRemover?.id ? 'Excluindo…' : 'Excluir rubrica'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </motion.div>
        </AppLayout>
    );
}
