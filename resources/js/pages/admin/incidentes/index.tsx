import { Head, useForm } from '@inertiajs/react';
import { AlertTriangle, Clock, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { LinhaIncidente, TipoIncidente } from '@/types/incidentes';

interface Props {
    incidentes: LinhaIncidente[];
    tipos: TipoIncidente[];
    prazo_original: string | null;
    prazo_efetivo: string | null;
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

export default function IncidentesIndex({ incidentes, tipos, prazo_original, prazo_efetivo }: Props) {
    const form = useForm({ kind: '', description: '', deadline_extension_minutes: '0' });

    const declarar: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('admin.incidentes.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const prazoEstendido = prazo_original !== null && prazo_efetivo !== null && prazo_original !== prazo_efetivo;

    return (
        <AppLayout breadcrumbs={[{ title: 'Incidentes', href: route('admin.incidentes.index') }]}>
            <Head title="Incidentes" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <header className="mb-6">
                    <h1 className="text-2xl font-semibold">Incidentes</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Declarar um incidente com extensão de prazo vale pra <strong>todas as equipes</strong>, nunca só pra quem avisou.
                    </p>
                </header>

                {prazo_original && (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border mb-6 flex items-center gap-2 rounded-xl border p-4 text-sm">
                        <Clock className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        <span>
                            Prazo de submissão original: <strong>{prazo_original}</strong>
                            {prazoEstendido && (
                                <>
                                    {' '}
                                    — efetivo agora: <strong>{prazo_efetivo}</strong>
                                </>
                            )}
                        </span>
                    </div>
                )}

                <section className="border-sidebar-border/70 dark:border-sidebar-border mb-6 rounded-xl border p-4 sm:p-6">
                    <h2 className="font-medium">Declarar incidente</h2>
                    <form onSubmit={declarar} className="mt-3 grid gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="kind">Tipo</Label>
                            <select
                                id="kind"
                                value={form.data.kind}
                                onChange={(e) => form.setData('kind', e.target.value)}
                                className={campo}
                                aria-describedby={form.errors.kind ? 'kind-erro' : undefined}
                            >
                                <option value="">Selecione</option>
                                {tipos.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.label}
                                    </option>
                                ))}
                            </select>
                            <InputError id="kind-erro" message={form.errors.kind} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">O que aconteceu</Label>
                            <textarea
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                rows={3}
                                className={areaTexto}
                                aria-describedby={form.errors.description ? 'description-erro' : undefined}
                            />
                            <InputError id="description-erro" message={form.errors.description} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="deadline_extension_minutes">Extensão de prazo (minutos, 0 se não houver)</Label>
                            <input
                                id="deadline_extension_minutes"
                                type="number"
                                min={0}
                                max={1440}
                                value={form.data.deadline_extension_minutes}
                                onChange={(e) => form.setData('deadline_extension_minutes', e.target.value)}
                                className={campo}
                                aria-describedby={form.errors.deadline_extension_minutes ? 'deadline_extension_minutes-erro' : undefined}
                            />
                            <InputError id="deadline_extension_minutes-erro" message={form.errors.deadline_extension_minutes} />
                        </div>

                        <div>
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                {form.processing ? 'Registrando…' : 'Declarar incidente'}
                            </Button>
                        </div>
                    </form>
                </section>

                <h2 className="mb-3 font-medium">Histórico</h2>
                {incidentes.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6 text-center">
                        <AlertTriangle className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Nenhum incidente registrado.</p>
                        <p className="text-muted-foreground mt-1 text-sm">Que continue assim até o fim do evento.</p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {incidentes.map((i) => (
                            <li key={i.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <p className="font-medium">{i.tipo_label}</p>
                                    <span className="text-muted-foreground text-xs">{i.declarado_em}</span>
                                </div>
                                <p className="mt-1 text-sm">{i.descricao}</p>
                                <p className="text-muted-foreground mt-2 text-xs">
                                    Declarado por {i.declarado_por}
                                    {i.extensao_minutos > 0 && ` · extensão de ${i.extensao_minutos} min`}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
