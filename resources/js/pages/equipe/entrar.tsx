import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

type EntrarForm = {
    invite_code: string;
};

export default function EntrarNaEquipe() {
    const { data, setData, post, processing, errors } = useForm<EntrarForm>({
        invite_code: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('teams.join.store'));
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Equipe', href: route('teams.show') },
                { title: 'Entrar com um código', href: route('teams.join.create') },
            ]}
        >
            <Head title="Entrar em uma equipe" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <header className="mb-8">
                    <h1 className="text-2xl font-semibold">Entrar em uma equipe</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Peça o código de convite para quem já criou a equipe e digite abaixo. Maiúsculas ou minúsculas, tanto faz.
                    </p>
                </header>

                <form onSubmit={submit} className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="invite_code">Código de convite</Label>
                        <Input
                            id="invite_code"
                            value={data.invite_code}
                            onChange={(e) => setData('invite_code', e.target.value)}
                            required
                            autoFocus
                            autoComplete="off"
                            maxLength={8}
                            placeholder="Ex.: AS3DYP"
                            className="font-mono text-lg tracking-[0.3em] uppercase"
                            aria-describedby={errors.invite_code ? 'invite_code-erro' : undefined}
                        />
                        <InputError id="invite_code-erro" message={errors.invite_code} />
                    </div>

                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        {processing ? 'Entrando…' : 'Entrar na equipe'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
