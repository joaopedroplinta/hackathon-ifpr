// Components
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, ShieldCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import CampoSenha from '@/components/hackathon/campo-senha';
import ResumoErro from '@/components/hackathon/resumo-erro';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.confirm'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Confirme que é você" description="Digite sua senha novamente para acessar esta área protegida.">
            <Head title="Confirmar senha" />

            <form onSubmit={submit} className="space-y-6" noValidate>
                <ResumoErro erros={errors} />
                <div className="bg-muted/50 text-muted-foreground flex items-start gap-3 rounded-2xl p-4 text-sm leading-6">
                    <ShieldCheck className="text-primary mt-0.5 size-5 shrink-0" aria-hidden="true" />
                    Esta confirmação protege alterações sensíveis na sua conta.
                </div>
                <div className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="password">Senha</Label>
                        <CampoSenha
                            id="password"
                            name="password"
                            placeholder="Sua senha"
                            autoComplete="current-password"
                            value={data.password}
                            autoFocus
                            onChange={(e) => setData('password', e.target.value)}
                            className="bg-muted/30 h-12"
                        />

                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center">
                        <Button className="h-12 w-full" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Confirmar senha
                        </Button>
                    </div>
                </div>
            </form>
        </AuthLayout>
    );
}
