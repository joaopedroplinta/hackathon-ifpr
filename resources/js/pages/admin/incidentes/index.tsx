import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
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
        form.post(route('painel.incidentes.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const prazoEstendido = prazo_original !== null && prazo_efetivo !== null && prazo_original !== prazo_efetivo;
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Incidentes', href: route('painel.incidentes.index') }]}>
            <Head title="Incidentes" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <header className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight">Incidentes</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Declarar um incidente com extensão de prazo vale pra <strong>todas as equipes</strong>, nunca só pra quem avisou.
                    </p>
                </header>

                {prazo_original && (
                    <div className="border-border bg-card mb-6 flex items-center gap-2 rounded-xl border p-4 text-sm">
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

                <section className="border-border bg-card mb-6 rounded-xl border p-4 sm:p-6">
                    <h2 className="font-semibold">Declarar incidente</h2>
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

                <h2 className="mb-3 font-semibold">Histórico</h2>
                {incidentes.length === 0 ? (
                    <div className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <AlertTriangle className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhum incidente registrado.</p>
                        <p className="text-muted-foreground text-sm">Que continue assim até o fim do evento.</p>
                    </div>
                ) : (
                    <ul className="border-border bg-card flex flex-col divide-y overflow-hidden rounded-xl border">
                        {incidentes.map((i) => (
                            <li key={i.id} className="p-4">
                                <div className="flex items-center justify-between gap-2">
                                    <p className="font-semibold">{i.tipo_label}</p>
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
            </motion.div>
        </AppLayout>
    );
}
