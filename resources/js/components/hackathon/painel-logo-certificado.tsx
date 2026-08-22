import { useForm } from '@inertiajs/react';
import { ImageUp, LoaderCircle, Upload } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CertificadoLogoEvento } from '@/types/evento-admin';

// `type`, não `interface`: mesma razão do restante do formulário -- CLAUDE.md.
type LogoForm = { logo: File | null };

type Props = { certificadoLogo: CertificadoLogoEvento };

export default function PainelLogoCertificado({ certificadoLogo }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, progress, errors, reset, clearErrors } = useForm<LogoForm>({
        logo: null,
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.evento.certificado.logo.upload'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                reset('logo');
                if (inputRef.current) {
                    inputRef.current.value = '';
                }
            },
        });
    };

    return (
        <section className="border-border bg-card rounded-xl border p-4 sm:p-6">
            <h2 className="font-semibold">Logo do certificado</h2>
            <p className="text-muted-foreground mt-1 text-sm">
                Aparece no topo do certificado em PDF. Certificados já emitidos mantêm o logo com que foram gerados.
            </p>

            {certificadoLogo.tem_logo ? (
                <div className="bg-muted mt-4 flex items-center gap-3 rounded-xl p-3">
                    <ImageUp className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                    <p className="text-sm font-semibold">Logo enviado</p>
                </div>
            ) : (
                <p className="text-muted-foreground mt-4 text-sm">Nenhum logo enviado ainda. O certificado sai só com o texto.</p>
            )}

            <form onSubmit={enviar} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                <div className="grid flex-1 gap-2">
                    <Label htmlFor="logo" className="sr-only">
                        {certificadoLogo.tem_logo ? 'Substituir logo' : 'Escolher logo'}
                    </Label>
                    <Input
                        id="logo"
                        ref={inputRef}
                        type="file"
                        accept=".png,.jpg,.jpeg"
                        onChange={(e) => {
                            clearErrors('logo');
                            setData('logo', e.target.files?.[0] ?? null);
                        }}
                        aria-describedby={errors.logo ? 'logo-erro' : undefined}
                    />
                    <InputError id="logo-erro" message={errors.logo} />
                    {progress && (
                        <p role="status" aria-live="polite" className="text-muted-foreground text-xs">
                            Enviando… {progress.percentage}%
                        </p>
                    )}
                </div>
                <Button type="submit" disabled={processing || !data.logo} className="shrink-0">
                    {processing ? (
                        <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                    ) : (
                        <Upload className="h-4 w-4" aria-hidden="true" />
                    )}
                    {processing ? 'Enviando…' : certificadoLogo.tem_logo ? 'Substituir' : 'Anexar'}
                </Button>
            </form>
        </section>
    );
}
