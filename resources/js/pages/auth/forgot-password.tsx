// Components
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
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
        <AuthLayout title="Esqueceu a senha?" description="Informe seu e-mail para receber o link de redefinição">
            <Head title="Esqueceu a senha" />

            {status && (
                <div role="status" className="bg-primary/10 text-primary rounded-xl p-3 text-center text-sm font-medium">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <form onSubmit={submit} noValidate>
                    <ResumoErro erros={errors} />
                    <div className="grid gap-2">
                        <Label htmlFor="email">E-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="off"
                            value={data.email}
                            autoFocus
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="voce@exemplo.com"
                        />

                        <InputError message={errors.email} />
                    </div>

                    <div className="my-6 flex items-center justify-start">
                        <Button className="w-full" disabled={processing}>
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
