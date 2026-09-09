import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import CampoSenha from '@/components/hackathon/campo-senha';
import { GoogleLoginButton } from '@/components/hackathon/google-login-button';
import ResumoErro from '@/components/hackathon/resumo-erro';
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
        <AuthLayout title="Boas-vindas de volta" description="Acesse sua equipe, acompanhe o evento e continue de onde parou.">
            <Head title="Entrar" />

            <div className="flex flex-col gap-7">
                {status && (
                    <div
                        role="status"
                        className="border-primary/20 bg-primary/5 text-foreground flex items-start gap-3 rounded-2xl border p-4 text-sm"
                    >
                        <CheckCircle2 className="text-primary mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <span>{status}</span>
                    </div>
                )}
                <GoogleLoginButton />

                <div className="relative text-center text-sm">
                    <span className="bg-background text-muted-foreground relative z-10 px-3">ou entre com e-mail</span>
                    <span className="border-border absolute inset-x-0 top-1/2 border-t" aria-hidden="true" />
                </div>

                <form className="flex flex-col gap-6" onSubmit={submit} noValidate>
                    <ResumoErro erros={errors} />
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
                                className="bg-muted/30 h-12"
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
                            <CampoSenha
                                id="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                placeholder="Sua senha"
                                className="bg-muted/30 h-12"
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

                        <Button type="submit" className="mt-2 h-12 w-full" tabIndex={4} disabled={processing}>
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
        </AuthLayout>
    );
}
