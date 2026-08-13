import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { useEffect, useRef } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Criterio, SubmissaoAvaliar } from '@/types/avaliacao';

interface Props {
    submissao: SubmissaoAvaliar;
    criterios: Criterio[];
    avaliacao: {
        overall_comment: string | null;
        notas: { criterion_id: number; score: number | null; comment: string | null }[];
    };
    somente_leitura: boolean;
}

type AvaliacaoForm = {
    scores: { criterion_id: number; score: number | null; comment: string }[];
    overall_comment: string;
};

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:opacity-60';

export default function AvaliarSubmissao({ submissao, criterios, avaliacao, somente_leitura: somenteLeitura }: Props) {
    const form = useForm<AvaliacaoForm>({
        scores: criterios.map((c) => {
            const nota = avaliacao.notas.find((n) => n.criterion_id === c.id);
            return { criterion_id: c.id, score: nota?.score ?? null, comment: nota?.comment ?? '' };
        }),
        overall_comment: avaliacao.overall_comment ?? '',
    });

    const primeiraRenderizacao = useRef(true);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        if (somenteLeitura) return;

        if (primeiraRenderizacao.current) {
            primeiraRenderizacao.current = false;
            return;
        }

        if (timeoutRef.current) clearTimeout(timeoutRef.current);

        timeoutRef.current = setTimeout(() => {
            form.post(route('jurado.avaliar.autosave', submissao.id), {
                preserveScroll: true,
                preserveState: true,
            });
        }, 800);

        return () => {
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [form.data.scores, form.data.overall_comment, somenteLeitura]);

    const atualizarNota = (criterionId: number, score: number | null) => {
        form.setData(
            'scores',
            form.data.scores.map((s) => (s.criterion_id === criterionId ? { ...s, score } : s)),
        );
    };

    const atualizarComentario = (criterionId: number, comment: string) => {
        form.setData(
            'scores',
            form.data.scores.map((s) => (s.criterion_id === criterionId ? { ...s, comment } : s)),
        );
    };

    const enviar = () => {
        if (timeoutRef.current) clearTimeout(timeoutRef.current);
        form.post(route('jurado.avaliar.enviar', submissao.id), { preserveScroll: true });
    };

    const todasPreenchidas = form.data.scores.every((s) => s.score !== null);

    // O Laravel devolve erro de item de array como "scores.0.score", mas o
    // tipo de InertiaFormProps só indexa por chave direta do form -- daí o
    // cast. Sem isto, rejeição de nota (ex.: acima do máximo do critério)
    // falha em silêncio: nenhuma mensagem chega ao jurado.
    const errosPorIndice = form.errors as unknown as Record<string, string | undefined>;

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Avaliar', href: route('jurado.index') },
                { title: submissao.titulo, href: route('jurado.avaliar.show', submissao.id) },
            ]}
        >
            <Head title={`Avaliar — ${submissao.titulo}`} />

            <div className="mx-auto w-full max-w-4xl p-4">
                {somenteLeitura && (
                    <div className="mb-6 flex items-center gap-2 rounded-xl border border-emerald-600/40 bg-emerald-600/10 p-4 text-sm text-emerald-700 dark:text-emerald-400">
                        <CheckCircle2 className="h-4 w-4 shrink-0" aria-hidden="true" />
                        Avaliação já enviada. Não é mais possível alterar a nota por aqui.
                    </div>
                )}

                <div className="grid gap-6 md:grid-cols-2">
                    <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 sm:p-6">
                        <h1 className="text-xl font-semibold">{submissao.titulo}</h1>
                        <p className="text-muted-foreground text-sm">{submissao.equipe}</p>

                        {submissao.resumo && <p className="mt-3 text-sm">{submissao.resumo}</p>}
                        {submissao.descricao && <p className="text-muted-foreground mt-3 text-sm whitespace-pre-line">{submissao.descricao}</p>}

                        <div className="mt-4 flex flex-col gap-1 text-sm">
                            {submissao.repo_url && (
                                <a href={submissao.repo_url} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">
                                    Repositório
                                </a>
                            )}
                            {submissao.video_url && (
                                <a href={submissao.video_url} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">
                                    Vídeo de demonstração
                                </a>
                            )}
                            {submissao.deploy_url && (
                                <a href={submissao.deploy_url} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">
                                    Aplicação publicada
                                </a>
                            )}
                        </div>
                    </section>

                    <section className="flex flex-col gap-4">
                        {criterios.length === 0 ? (
                            <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 text-sm">
                                Nenhuma rubrica ativa para este evento. Fale com o organizador antes de avaliar.
                            </div>
                        ) : (
                            criterios.map((criterio, indice) => {
                                const nota = form.data.scores.find((s) => s.criterion_id === criterio.id);
                                const erroNota = errosPorIndice[`scores.${indice}.score`];

                                return (
                                    <div key={criterio.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                                        <div className="flex items-baseline justify-between gap-3">
                                            <Label htmlFor={`score-${criterio.id}`} className="font-medium">
                                                {criterio.nome}
                                            </Label>
                                            <span className="text-muted-foreground shrink-0 text-xs">
                                                peso {criterio.peso} · até {criterio.nota_maxima}
                                            </span>
                                        </div>
                                        {criterio.descricao && <p className="text-muted-foreground mt-1 text-sm">{criterio.descricao}</p>}

                                        <input
                                            id={`score-${criterio.id}`}
                                            type="number"
                                            inputMode="decimal"
                                            min={0}
                                            max={criterio.nota_maxima}
                                            step={0.5}
                                            value={nota?.score ?? ''}
                                            disabled={somenteLeitura}
                                            onChange={(e) => atualizarNota(criterio.id, e.target.value === '' ? null : Number(e.target.value))}
                                            aria-describedby={erroNota ? `score-${criterio.id}-erro` : undefined}
                                            className="border-input bg-background focus-visible:ring-ring mt-3 h-12 w-24 rounded-md border px-3 text-lg focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:opacity-60"
                                        />
                                        <InputError id={`score-${criterio.id}-erro`} message={erroNota} />

                                        <textarea
                                            value={nota?.comment ?? ''}
                                            disabled={somenteLeitura}
                                            onChange={(e) => atualizarComentario(criterio.id, e.target.value)}
                                            placeholder="Comentário (opcional)"
                                            aria-label={`Comentário sobre ${criterio.nome}`}
                                            rows={2}
                                            className={`${areaTexto} mt-3`}
                                        />
                                    </div>
                                );
                            })
                        )}

                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                            <Label htmlFor="overall_comment">Comentário geral (opcional)</Label>
                            <textarea
                                id="overall_comment"
                                value={form.data.overall_comment}
                                disabled={somenteLeitura}
                                onChange={(e) => form.setData('overall_comment', e.target.value)}
                                rows={3}
                                className={`${areaTexto} mt-2`}
                            />
                        </div>

                        <InputError message={form.errors.scores} />

                        {!somenteLeitura && (
                            <>
                                <p className="text-muted-foreground -mb-2 text-right text-xs" aria-live="polite">
                                    {form.processing ? 'Salvando rascunho…' : form.recentlySuccessful ? 'Rascunho salvo.' : ''}
                                </p>

                                <Button onClick={enviar} disabled={form.processing || !todasPreenchidas} className="w-full">
                                    {form.processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                    Enviar avaliação
                                </Button>
                            </>
                        )}

                        <Link href={route('jurado.index')} className="text-muted-foreground text-center text-sm hover:underline">
                            ← Voltar para a fila
                        </Link>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
