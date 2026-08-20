import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Award, CircleCheck, Clock, FileText } from 'lucide-react';
import { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { LinhaCertificado, TipoCertificado } from '@/types/certificados';

interface Props {
    certificados: LinhaCertificado[];
    pessoas: { id: number; nome: string }[];
    tipos: TipoCertificado[];
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

export default function CertificadosIndex({ certificados, pessoas, tipos }: Props) {
    const form = useForm({ user_id: '', type: '', colocacao: '' });

    const emitir: FormEventHandler = (e) => {
        e.preventDefault();
        form.post(route('admin.certificados.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Certificados', href: route('admin.certificados.index') }]}>
            <Head title="Certificados" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-4xl p-4 sm:p-6">
                <header className="mb-6">
                    <h1 className="text-2xl font-medium tracking-tight">Certificados</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Participação, jurado, organizador e colocação saem em lote com{' '}
                        <code className="text-xs">php artisan hackathon:issue-certificates</code>. Aqui é só a emissão avulsa — mentoria ou correção
                        pontual.
                    </p>
                </header>

                <section className="bg-card mb-6 rounded-2xl p-4 sm:p-6">
                    <h2 className="font-medium">Emitir certificado avulso</h2>
                    <form onSubmit={emitir} className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="flex-1">
                            <Label htmlFor="user_id">Pessoa</Label>
                            <select
                                id="user_id"
                                value={form.data.user_id}
                                onChange={(e) => form.setData('user_id', e.target.value)}
                                className={campo}
                                aria-describedby={form.errors.user_id ? 'user_id-erro' : undefined}
                            >
                                <option value="">Selecione</option>
                                {pessoas.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.nome}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex-1">
                            <Label htmlFor="type">Tipo</Label>
                            <select
                                id="type"
                                value={form.data.type}
                                onChange={(e) => form.setData('type', e.target.value)}
                                className={campo}
                                aria-describedby={form.errors.type ? 'type-erro' : undefined}
                            >
                                <option value="">Selecione</option>
                                {tipos.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {form.data.type === 'colocacao' && (
                            <div className="flex-1">
                                <Label htmlFor="colocacao">Texto da colocação</Label>
                                <input
                                    id="colocacao"
                                    value={form.data.colocacao}
                                    onChange={(e) => form.setData('colocacao', e.target.value)}
                                    placeholder="Ex.: 1º lugar geral"
                                    className={campo}
                                />
                            </div>
                        )}
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Emitindo…' : 'Emitir'}
                        </Button>
                    </form>
                    {form.errors.user_id && (
                        <p id="user_id-erro" className="mt-2 text-sm text-red-600">
                            {form.errors.user_id}
                        </p>
                    )}
                    {form.errors.type && (
                        <p id="type-erro" className="mt-2 text-sm text-red-600">
                            {form.errors.type}
                        </p>
                    )}
                </section>

                <h2 className="mb-3 font-medium">Emitidos</h2>
                {certificados.length === 0 ? (
                    <div className="bg-card flex flex-col items-center gap-3 rounded-2xl p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Award className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-medium">Nenhum certificado emitido ainda.</p>
                        <p className="text-muted-foreground text-sm">Rode o comando de emissão em lote ou emita um avulso acima.</p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[32rem] text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-b text-left">
                                    <th className="py-2 pr-3 font-medium">Pessoa</th>
                                    <th className="py-2 pr-3 font-medium">Tipo</th>
                                    <th className="py-2 pr-3 font-medium">Emitido em</th>
                                    <th className="py-2 pr-3 font-medium">PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                {certificados.map((c) => (
                                    <tr key={c.id} className="border-b last:border-0">
                                        <td className="py-2 pr-3">{c.nome}</td>
                                        <td className="py-2 pr-3">{c.tipo_label}</td>
                                        <td className="py-2 pr-3">{c.emitido_em}</td>
                                        <td className="py-2 pr-3">
                                            {c.pronto ? (
                                                <span className="inline-flex items-center gap-1 text-green-700 dark:text-green-500">
                                                    <CircleCheck className="h-4 w-4" aria-hidden="true" /> Pronto
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground inline-flex items-center gap-1">
                                                    <Clock className="h-4 w-4" aria-hidden="true" /> Gerando…
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                <p className="text-muted-foreground mt-6 flex items-center gap-1.5 text-xs">
                    <FileText className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    Qualquer certificado pode ser conferido publicamente em <code>/validar/&#123;código&#125;</code>.
                </p>
            </motion.div>
        </AppLayout>
    );
}
