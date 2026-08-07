import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import { GoogleLoginButton } from '@/components/hackathon/google-login-button';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Entrar na sua conta" description="Informe seu e-mail e senha para entrar">
            <Head title="Entrar" />

            <div className="flex flex-col gap-6">
                <GoogleLoginButton />

                <div className="relative text-center text-sm">
                    <span className="bg-background text-muted-foreground relative z-10 px-2">ou</span>
                    <span className="border-border absolute inset-x-0 top-1/2 border-t" aria-hidden="true" />
                </div>

                <form className="flex flex-col gap-6" onSubmit={submit}>
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">E-mail</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="voce@exemplo.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Senha</Label>
                                {canResetPassword && (
                                    <TextLink href={route('password.request')} className="ml-auto text-sm" tabIndex={5}>
                                        Esqueceu a senha?
                                    </TextLink>
                                )}
                            </div>
                            <Input
                                id="password"
                                type="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Sua senha"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center space-x-3">
                            {/* Sem checked/onCheckedChange o campo nunca chegava ao servidor:
                                data.remember ficava false por mais que o usuário marcasse. */}
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                                checked={data.remember}
                                onCheckedChange={(checked) => setData('remember', checked === true)}
                            />
                            <Label htmlFor="remember">Manter conectado</Label>
                        </div>

                        <Button type="submit" className="mt-4 w-full" tabIndex={4} disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {processing ? 'Entrando…' : 'Entrar'}
                        </Button>
                    </div>

                    <div className="text-muted-foreground text-center text-sm">
                        Ainda não tem conta?{' '}
                        <TextLink href={route('register')} tabIndex={5}>
                            Cadastre-se
                        </TextLink>
                    </div>
                </form>
            </div>

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}
        </AuthLayout>
    );
}
