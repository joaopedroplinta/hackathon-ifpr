import { router, useForm } from '@inertiajs/react';
import { Download, LoaderCircle, Paperclip, Trash2 } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArquivosSubmissao } from '@/types/submissao';

// `type`, não `interface`: o useForm do Inertia v2 exige a index signature
// implícita que só o `type` ganha -- ver CLAUDE.md.
type ArquivoForm = { arquivo: File | null };

type Props = {
    arquivos: ArquivosSubmissao;
    /** A mesma janela de edição da submissão: fechada, some o formulário. */
    podeAnexar: boolean;
};

export default function PainelArquivos({ arquivos, podeAnexar }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, progress, errors, reset, clearErrors } = useForm<ArquivoForm>({
        arquivo: null,
    });

    const cheio = arquivos.itens.length >= arquivos.limite;

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('submission-files.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                reset('arquivo');
                // reset() limpa o estado do React, mas o <input type="file">
                // guarda o arquivo escolhido no próprio DOM: sem isto o nome
                // continua na tela depois de anexado.
                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
        });
    };

    const remover = (id: number) => {
        router.delete(route('submission-files.destroy', id), { preserveScroll: true });
    };

    return (
        <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 sm:p-6">
            <h2 className="font-medium">Arquivos</h2>
            <p className="text-muted-foreground mt-1 text-sm">
                Slide do pitch, código compactado ou print da tela. PDF, ZIP, PNG ou JPG, até 25 MB cada — no máximo {arquivos.limite} arquivos.
            </p>

            {arquivos.itens.length === 0 ? (
                <p className="text-muted-foreground mt-4 text-sm">Nenhum arquivo anexado. Os links do projeto continuam valendo sem isto.</p>
            ) : (
                <ul className="mt-4 divide-y">
                    {arquivos.itens.map((arquivo) => (
                        <li key={arquivo.id} className="flex items-center gap-3 py-3">
                            <Paperclip className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">{arquivo.nome}</p>
                                <p className="text-muted-foreground text-xs">{arquivo.tamanho}</p>
                            </div>
                            <a
                                href={route('submission-files.download', arquivo.id)}
                                className="text-muted-foreground hover:text-foreground inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md"
                                aria-label={`Baixar ${arquivo.nome}`}
                            >
                                <Download className="h-4 w-4" aria-hidden="true" />
                            </a>
                            {arquivo.pode_remover && (
                                <button
                                    type="button"
                                    onClick={() => remover(arquivo.id)}
                                    className="text-muted-foreground hover:text-destructive inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md"
                                    aria-label={`Remover ${arquivo.nome}`}
                                >
                                    <Trash2 className="h-4 w-4" aria-hidden="true" />
                                </button>
                            )}
                        </li>
                    ))}
                </ul>
            )}

            {podeAnexar &&
                (cheio ? (
                    <p className="text-muted-foreground mt-4 text-sm">Limite de {arquivos.limite} arquivos atingido. Remova um para anexar outro.</p>
                ) : (
                    <form onSubmit={enviar} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="arquivo" className="sr-only">
                                Escolher arquivo
                            </Label>
                            <Input
                                id="arquivo"
                                ref={inputRef}
                                type="file"
                                accept=".pdf,.zip,.png,.jpg,.jpeg"
                                onChange={(e) => {
                                    clearErrors('arquivo');
                                    setData('arquivo', e.target.files?.[0] ?? null);
                                }}
                                aria-describedby={errors.arquivo ? 'arquivo-erro' : undefined}
                            />
                            <InputError id="arquivo-erro" message={errors.arquivo} />
                            {progress && (
                                <p role="status" aria-live="polite" className="text-muted-foreground text-xs">
                                    Enviando… {progress.percentage}%
                                </p>
                            )}
                        </div>
                        <Button type="submit" disabled={processing || !data.arquivo} className="shrink-0">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            {processing ? 'Anexando…' : 'Anexar'}
                        </Button>
                    </form>
                ))}
        </section>
    );
}
