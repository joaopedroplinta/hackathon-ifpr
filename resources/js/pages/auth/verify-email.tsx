// Components
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
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
        <AuthLayout title="Verifique seu e-mail" description="Clique no link que enviamos para o seu e-mail para confirmar o endereço.">
            <Head title="Verificação de e-mail" />

            {status === 'verification-link-sent' && (
                <div role="status" className="bg-primary/10 text-primary mb-5 rounded-xl p-3 text-center text-sm font-medium">
                    Enviamos um novo link de verificação para o e-mail informado no cadastro.
                </div>
            )}

            <form onSubmit={submit} className="space-y-6 text-center">
                <Button disabled={processing} className="h-11 w-full">
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    Reenviar e-mail de verificação
                </Button>

                <TextLink href={route('logout')} method="post" className="inline-flex min-h-11 items-center text-sm">
                    Sair desta conta
                </TextLink>
            </form>
        </AuthLayout>
    );
}
