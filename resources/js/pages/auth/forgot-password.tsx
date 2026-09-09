// Components
import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

import ResumoErro from '@/components/hackathon/resumo-erro';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <AuthLayout title="Recupere seu acesso" description="Informe o e-mail da sua conta. Enviaremos um link seguro para criar uma nova senha.">
            <Head title="Esqueceu a senha" />

            {status && (
                <div role="status" className="border-primary/20 bg-primary/5 flex items-start gap-3 rounded-2xl border p-4 text-sm">
                    <CheckCircle2 className="text-primary mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <span>{status}</span>
                </div>
            )}

            <div className="space-y-6">
                <form onSubmit={submit} noValidate>
                    <ResumoErro erros={errors} />
                    <div className="grid gap-2">
                        <Label htmlFor="email">E-mail</Label>
                        <div className="relative">
                            <Mail
                                className="text-muted-foreground pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2"
                                aria-hidden="true"
                            />
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                autoComplete="email"
                                value={data.email}
                                autoFocus
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="voce@exemplo.com"
                                className="bg-muted/30 h-12 pl-11"
                            />
                        </div>

                        <InputError message={errors.email} />
                    </div>

                    <div className="my-6 flex items-center justify-start">
                        <Button className="h-12 w-full" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Enviar link de redefinição
                        </Button>
                    </div>
                </form>

                <div className="text-muted-foreground space-x-1 text-center text-sm">
                    <span>Ou volte para</span>
                    <TextLink href={route('login')}>entrar</TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
