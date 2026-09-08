// Components
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
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
        <AuthLayout title="Confirme sua senha" description="Esta é uma área protegida. Confirme sua senha para continuar.">
            <Head title="Confirmar senha" />

            <form onSubmit={submit} noValidate>
                <ResumoErro erros={errors} />
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
                        />

                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center">
                        <Button className="w-full" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Confirmar senha
                        </Button>
                    </div>
                </div>
            </form>
        </AuthLayout>
    );
}
