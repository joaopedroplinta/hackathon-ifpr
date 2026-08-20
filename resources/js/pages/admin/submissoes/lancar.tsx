import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { EquipeParaLancamento, FonteSubmissaoManual } from '@/types/submissao-manual';

interface Props {
    equipes: EquipeParaLancamento[];
    fontes: FonteSubmissaoManual[];
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

/**
 * Brasil não tem mais horário de verão desde 2019 -- América/São_Paulo é
 * -03:00 o ano inteiro. Mesmo helper de admin/evento/editar.tsx.
 */
function paraIsoDeSaoPaulo(valorDatetimeLocal: string): string | null {
    if (!valorDatetimeLocal) {
        return null;
    }

    return new Date(`${valorDatetimeLocal}:00-03:00`).toISOString();
}

export default function LancarSubmissao({ equipes, fontes }: Props) {
    const { data, setData, transform, post, processing, errors } = useForm({
        team_id: '',
        title: '',
        summary: '',
        repo_url: '',
        video_url: '',
        original_submitted_at: '',
        source: '',
    });

    transform((dadosAtuais) => ({
        ...dadosAtuais,
        original_submitted_at: paraIsoDeSaoPaulo(dadosAtuais.original_submitted_at),
    }));

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.submissions.record.store'));
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Submissões', href: route('admin.submissions.index') },
                { title: 'Lançar manualmente', href: '#' },
            ]}
        >
            <Head title="Lançar submissão manualmente" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <h1 className="font-display mb-1 text-2xl font-semibold tracking-tight">Lançar submissão manualmente</h1>
                <p className="text-muted-foreground mb-6 text-sm">
                    Só pra quando a equipe não conseguiu usar o formulário web de jeito nenhum -- recebeu por e-mail ou entregou no papel (plano B,
                    degraus 3 e 4). Fica marcada pra conferência no painel.
                </p>

                {equipes.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6 text-center">
                        <p className="font-medium">Nenhuma equipe pendente.</p>
                        <p className="text-muted-foreground mt-1 text-sm">Toda equipe deste evento já tem uma submissão registrada.</p>
                    </div>
                ) : (
                    <form onSubmit={enviar} className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="team_id">Equipe</Label>
                            <select
                                id="team_id"
                                value={data.team_id}
                                onChange={(e) => setData('team_id', e.target.value)}
                                className={campo}
                                aria-describedby={errors.team_id ? 'team_id-erro' : undefined}
                            >
                                <option value="">Selecione</option>
                                {equipes.map((equipe) => (
                                    <option key={equipe.id} value={equipe.id}>
                                        {equipe.nome}
                                    </option>
                                ))}
                            </select>
                            <InputError id="team_id-erro" message={errors.team_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="source">Como a equipe entregou</Label>
                            <select
                                id="source"
                                value={data.source}
                                onChange={(e) => setData('source', e.target.value)}
                                className={campo}
                                aria-describedby={errors.source ? 'source-erro' : undefined}
                            >
                                <option value="">Selecione</option>
                                {fontes.map((fonte) => (
                                    <option key={fonte.value} value={fonte.value}>
                                        {fonte.label}
                                    </option>
                                ))}
                            </select>
                            <InputError id="source-erro" message={errors.source} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="original_submitted_at">Horário real da entrega</Label>
                            <Input
                                id="original_submitted_at"
                                type="datetime-local"
                                value={data.original_submitted_at}
                                onChange={(e) => setData('original_submitted_at', e.target.value)}
                                aria-describedby={errors.original_submitted_at ? 'original_submitted_at-erro' : undefined}
                            />
                            <InputError id="original_submitted_at-erro" message={errors.original_submitted_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="repo_url">Link do repositório</Label>
                            <Input
                                id="repo_url"
                                value={data.repo_url}
                                onChange={(e) => setData('repo_url', e.target.value)}
                                placeholder="https://github.com/..."
                                aria-describedby={errors.repo_url ? 'repo_url-erro' : undefined}
                            />
                            <InputError id="repo_url-erro" message={errors.repo_url} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="title">Título (opcional)</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                maxLength={120}
                                aria-describedby={errors.title ? 'title-erro' : undefined}
                            />
                            <InputError id="title-erro" message={errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="summary">Resumo (opcional)</Label>
                            <Input
                                id="summary"
                                value={data.summary}
                                onChange={(e) => setData('summary', e.target.value)}
                                maxLength={300}
                                aria-describedby={errors.summary ? 'summary-erro' : undefined}
                            />
                            <InputError id="summary-erro" message={errors.summary} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="video_url">Link do vídeo (opcional)</Label>
                            <Input
                                id="video_url"
                                value={data.video_url}
                                onChange={(e) => setData('video_url', e.target.value)}
                                aria-describedby={errors.video_url ? 'video_url-erro' : undefined}
                            />
                            <InputError id="video_url-erro" message={errors.video_url} />
                        </div>

                        <div>
                            <Button type="submit" disabled={processing}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                {processing ? 'Lançando…' : 'Lançar submissão'}
                            </Button>
                        </div>
                    </form>
                )}
            </motion.div>
        </AppLayout>
    );
}
