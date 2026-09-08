import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

import CampoSenha from '@/components/hackathon/campo-senha';
import ResumoErro from '@/components/hackathon/resumo-erro';
import SecaoFormulario from '@/components/hackathon/secao-formulario';
import PasswordRequirements from '@/components/password-requirements';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configurações de senha',
        href: '/settings/password',
    },
];

export default function Password() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                if (errors.password) {
                    reset('password', 'password_confirmation');
                    passwordInput.current?.focus();
                }

                if (errors.current_password) {
                    reset('current_password');
                    currentPasswordInput.current?.focus();
                }
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configurações de senha" />

            <SettingsLayout>
                <div className="space-y-6">
                    <form onSubmit={updatePassword} className="space-y-6" noValidate>
                        <ResumoErro erros={errors} />
                        <SecaoFormulario titulo="Atualizar senha" instrucao="Use uma senha longa e aleatória para manter sua conta segura.">
                            <div className="grid gap-2">
                                <Label htmlFor="current_password">Senha atual</Label>

                                <CampoSenha
                                    id="current_password"
                                    ref={currentPasswordInput}
                                    value={data.current_password}
                                    onChange={(e) => setData('current_password', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="current-password"
                                    placeholder="Senha atual"
                                />

                                <InputError message={errors.current_password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Nova senha</Label>

                                <CampoSenha
                                    id="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    placeholder="Nova senha"
                                    aria-describedby="password-requisitos"
                                />

                                <PasswordRequirements senha={data.password} />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Confirmar senha</Label>

                                <CampoSenha
                                    id="password_confirmation"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    placeholder="Confirmar senha"
                                />

                                <InputError message={errors.password_confirmation} />
                            </div>
                            <div className="border-border flex flex-wrap items-center gap-4 border-t pt-5">
                                <Button disabled={processing}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                    {processing ? 'Salvando…' : 'Salvar senha'}
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p role="status" className="text-primary text-sm font-medium">
                                        Senha atualizada.
                                    </p>
                                </Transition>
                            </div>
                        </SecaoFormulario>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
