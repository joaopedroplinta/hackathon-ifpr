import { useForm } from '@inertiajs/react';
import { FileText, LoaderCircle, Upload } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RegulamentoEvento } from '@/types/evento-admin';

// `type`, não `interface`: mesma razão do restante do formulário -- CLAUDE.md.
type RegulamentoForm = { regulamento: File | null };

type Props = { regulamento: RegulamentoEvento };

export default function PainelRegulamento({ regulamento }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, progress, errors, reset, clearErrors } = useForm<RegulamentoForm>({
        regulamento: null,
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('painel.evento.regulamento.upload'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                reset('regulamento');
                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
        });
    };

    return (
        <section className="border-border bg-card rounded-xl border p-4 sm:p-6">
            <h2 className="font-semibold">Regulamento em PDF</h2>
            <p className="text-muted-foreground mt-1 text-sm">
                Anexe o edital oficial. Aparece com um botão "Baixar PDF" na página pública <span className="font-mono">/regulamento</span>.
            </p>

            {regulamento.nome_arquivo ? (
                <div className="bg-muted mt-4 flex items-center gap-3 rounded-xl p-3">
                    <FileText className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold">{regulamento.nome_arquivo}</p>
                        <p className="text-muted-foreground text-xs">Atualizado em {regulamento.atualizado_em}</p>
                    </div>
                </div>
            ) : (
                <p className="text-muted-foreground mt-4 text-sm">Nenhum arquivo anexado ainda. A página pública mostra só o texto.</p>
            )}

            <form onSubmit={enviar} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                <div className="grid flex-1 gap-2">
                    <Label htmlFor="regulamento" className="sr-only">
                        {regulamento.nome_arquivo ? 'Substituir PDF' : 'Escolher PDF'}
                    </Label>
                    <Input
                        id="regulamento"
                        ref={inputRef}
                        type="file"
                        accept=".pdf"
                        onChange={(e) => {
                            clearErrors('regulamento');
                            setData('regulamento', e.target.files?.[0] ?? null);
                        }}
                        aria-describedby={errors.regulamento ? 'regulamento-erro' : undefined}
                    />
                    <InputError id="regulamento-erro" message={errors.regulamento} />
                    {progress && (
                        <p role="status" aria-live="polite" className="text-muted-foreground text-xs">
                            Enviando… {progress.percentage}%
                        </p>
                    )}
                </div>
                <Button type="submit" disabled={processing || !data.regulamento} className="shrink-0">
                    {processing ? (
                        <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                    ) : (
                        <Upload className="h-4 w-4" aria-hidden="true" />
                    )}
                    {processing ? 'Enviando…' : regulamento.nome_arquivo ? 'Substituir' : 'Anexar'}
                </Button>
            </form>
        </section>
    );
}
