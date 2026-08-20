import { Head, Link, router, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle, Pencil, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Criterio, CriterioForm } from '@/types/rubrica';

interface Props {
    rubrica: { id: number; nome: string; ativa: boolean };
    criterios: Criterio[];
}

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

const formVazio: CriterioForm = { name: '', description: '', weight: '', max_score: '' };

export default function MostrarRubrica({ rubrica, criterios }: Props) {
    const somaPesos = criterios.reduce((soma, c) => soma + c.peso, 0);
    const [editandoId, setEditandoId] = useState<number | null>(null);
    const [removendoId, setRemovendoId] = useState<number | null>(null);

    const novoForm = useForm<CriterioForm>(formVazio);
    const editarForm = useForm<CriterioForm>(formVazio);

    const criarCriterio: FormEventHandler = (e) => {
        e.preventDefault();
        novoForm.post(route('admin.rubrica.criteria.store', rubrica.id), { onSuccess: () => novoForm.reset() });
    };

    const abrirEdicao = (criterio: Criterio) => {
        setEditandoId(criterio.id);
        editarForm.setData({
            name: criterio.nome,
            description: criterio.descricao ?? '',
            weight: String(criterio.peso),
            max_score: String(criterio.nota_maxima),
        });
    };

    const salvarEdicao: FormEventHandler = (e) => {
        e.preventDefault();
        if (editandoId === null) return;

        editarForm.patch(route('admin.rubrica.criteria.update', editandoId), {
            onSuccess: () => setEditandoId(null),
        });
    };

    const remover = (criterio: Criterio) => {
        setRemovendoId(criterio.id);
        router.delete(route('admin.rubrica.criteria.destroy', criterio.id), {
            preserveScroll: true,
            onFinish: () => setRemovendoId(null),
        });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Rubrica', href: route('admin.rubrica.index') },
                { title: rubrica.nome, href: route('admin.rubrica.show', rubrica.id) },
            ]}
        >
            <Head title={`Rubrica — ${rubrica.nome}`} />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <header className="mb-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <h1 className="font-display text-2xl font-semibold tracking-tight">{rubrica.nome}</h1>
                        <span
                            className={`inline-block rounded border px-2 py-0.5 font-mono text-xs ${
                                rubrica.ativa
                                    ? 'border-emerald-600/40 text-emerald-700 dark:text-emerald-400'
                                    : 'border-muted-foreground/30 text-muted-foreground'
                            }`}
                        >
                            [{rubrica.ativa ? 'ativa' : 'inativa'}]
                        </span>
                    </div>
                    <p className="text-muted-foreground mt-1 text-sm">Soma dos pesos: {somaPesos}.</p>
                </header>

                {criterios.length === 0 ? (
                    <p className="text-muted-foreground mb-6 text-sm">Nenhum critério ainda. Adicione o primeiro abaixo.</p>
                ) : (
                    <ol className="mb-6 flex flex-col gap-3">
                        {criterios.map((criterio) =>
                            editandoId === criterio.id ? (
                                <li key={criterio.id} className="rounded-xl border p-4">
                                    <form onSubmit={salvarEdicao} className="grid gap-3">
                                        <div className="grid gap-2">
                                            <Label htmlFor={`edit-name-${criterio.id}`}>Nome</Label>
                                            <Input
                                                id={`edit-name-${criterio.id}`}
                                                value={editarForm.data.name}
                                                onChange={(e) => editarForm.setData('name', e.target.value)}
                                            />
                                            <InputError message={editarForm.errors.name} />
                                        </div>
                                        <div className="grid grid-cols-2 gap-3">
                                            <div className="grid gap-2">
                                                <Label htmlFor={`edit-weight-${criterio.id}`}>Peso</Label>
                                                <Input
                                                    id={`edit-weight-${criterio.id}`}
                                                    inputMode="decimal"
                                                    value={editarForm.data.weight}
                                                    onChange={(e) => editarForm.setData('weight', e.target.value)}
                                                />
                                                <InputError message={editarForm.errors.weight} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor={`edit-max-${criterio.id}`}>Nota máxima</Label>
                                                <Input
                                                    id={`edit-max-${criterio.id}`}
                                                    inputMode="numeric"
                                                    value={editarForm.data.max_score}
                                                    onChange={(e) => editarForm.setData('max_score', e.target.value)}
                                                />
                                                <InputError message={editarForm.errors.max_score} />
                                            </div>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor={`edit-desc-${criterio.id}`}>Descrição (opcional)</Label>
                                            <textarea
                                                id={`edit-desc-${criterio.id}`}
                                                value={editarForm.data.description}
                                                onChange={(e) => editarForm.setData('description', e.target.value)}
                                                rows={2}
                                                className={areaTexto}
                                            />
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="submit" size="sm" disabled={editarForm.processing}>
                                                {editarForm.processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                                Salvar
                                            </Button>
                                            <Button type="button" size="sm" variant="ghost" onClick={() => setEditandoId(null)}>
                                                Cancelar
                                            </Button>
                                        </div>
                                    </form>
                                </li>
                            ) : (
                                <li key={criterio.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p className="font-medium">{criterio.nome}</p>
                                            <p className="text-muted-foreground text-xs">
                                                peso {criterio.peso} · nota até {criterio.nota_maxima}
                                            </p>
                                            {criterio.descricao && <p className="text-muted-foreground mt-1 text-sm">{criterio.descricao}</p>}
                                        </div>
                                        <div className="flex shrink-0 items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Editar ${criterio.nome}`}
                                                onClick={() => abrirEdicao(criterio)}
                                            >
                                                <Pencil className="h-4 w-4" aria-hidden="true" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                aria-label={`Remover ${criterio.nome}`}
                                                disabled={removendoId === criterio.id}
                                                onClick={() => remover(criterio)}
                                                className="hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" aria-hidden="true" />
                                            </Button>
                                        </div>
                                    </div>
                                </li>
                            ),
                        )}
                    </ol>
                )}

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 sm:p-6">
                    <h2 className="font-medium">Novo critério</h2>
                    <form onSubmit={criarCriterio} className="mt-4 grid gap-3">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                value={novoForm.data.name}
                                onChange={(e) => novoForm.setData('name', e.target.value)}
                                placeholder="Ex.: Inovação"
                            />
                            <InputError message={novoForm.errors.name} />
                        </div>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label htmlFor="weight">Peso</Label>
                                <Input
                                    id="weight"
                                    inputMode="decimal"
                                    value={novoForm.data.weight}
                                    onChange={(e) => novoForm.setData('weight', e.target.value)}
                                    placeholder="Ex.: 2"
                                />
                                <InputError message={novoForm.errors.weight} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="max_score">Nota máxima</Label>
                                <Input
                                    id="max_score"
                                    inputMode="numeric"
                                    value={novoForm.data.max_score}
                                    onChange={(e) => novoForm.setData('max_score', e.target.value)}
                                    placeholder="Ex.: 10"
                                />
                                <InputError message={novoForm.errors.max_score} />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">Descrição (opcional)</Label>
                            <textarea
                                id="description"
                                value={novoForm.data.description}
                                onChange={(e) => novoForm.setData('description', e.target.value)}
                                rows={2}
                                className={areaTexto}
                            />
                        </div>
                        <Button type="submit" disabled={novoForm.processing} className="w-full sm:w-auto">
                            {novoForm.processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            Adicionar critério
                        </Button>
                    </form>
                </section>

                <Link href={route('admin.rubrica.index')} className="text-muted-foreground mt-6 inline-block text-sm hover:underline">
                    ← Voltar para a lista
                </Link>
            </motion.div>
        </AppLayout>
    );
}
