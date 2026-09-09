// Components
import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <AuthLayout title="Confirme seu e-mail" description="Abra a mensagem que enviamos e use o link para liberar todos os recursos da sua conta.">
            <Head title="Verificação de e-mail" />

            {status === 'verification-link-sent' && (
                <div role="status" className="border-primary/20 bg-primary/5 mb-5 flex items-start gap-3 rounded-2xl border p-4 text-left text-sm">
                    <CheckCircle2 className="text-primary mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <span>Enviamos um novo link de verificação para o e-mail informado no cadastro.</span>
                </div>
            )}

            <form onSubmit={submit} className="space-y-6 text-center">
                <div className="bg-muted/50 mx-auto flex size-16 items-center justify-center rounded-2xl">
                    <MailCheck className="text-primary size-7" aria-hidden="true" />
                </div>
                <p className="text-muted-foreground text-sm leading-6">Não recebeu? Verifique a caixa de spam ou solicite outro e-mail.</p>
                <Button disabled={processing} className="h-12 w-full">
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    {processing ? 'Enviando…' : 'Reenviar e-mail de verificação'}
                </Button>

                <TextLink href={route('logout')} method="post" className="inline-flex min-h-11 items-center text-sm">
                    Sair desta conta
                </TextLink>
            </form>
        </AuthLayout>
    );
}
