import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import AvatarUpload from '@/components/hackathon/avatar-upload';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type IdentidadeInstitucional, type TipoVinculo } from '@/types/profile';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configurações de perfil',
        href: '/settings/profile',
    },
];

/** "12345678900" -> "123.456.789-00", só pra leitura -- o servidor guarda e valida só dígito. */
function formatarCpf(valor: string): string {
    const digitos = valor.replace(/\D/g, '').slice(0, 11);

    return digitos
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

const opcoesVinculo: { value: TipoVinculo; label: string }[] = [
    { value: 'aluno_ifpr', label: 'Aluno do IFPR' },
    { value: 'professor_ifpr', label: 'Professor do IFPR' },
    { value: 'externo', label: 'Externo' },
];

export default function Profile({
    mustVerifyEmail,
    status,
    identidade,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    identidade: IdentidadeInstitucional;
}) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: auth.user.name,
        email: auth.user.email,
        cpf: identidade.cpf ?? '',
        tipo_vinculo: identidade.tipo_vinculo ?? '',
        matricula_suap: identidade.matricula_suap ?? '',
        matricula_siape: identidade.matricula_siape ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configurações de perfil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Foto de perfil" description="Aparece pra colegas de equipe, jurados e organização" />
                    <AvatarUpload nome={auth.user.name} avatarUrl={auth.user.avatar ?? null} />

                    <HeadingSmall title="Informações do perfil" description="Atualize seu nome e e-mail" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nome</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Nome completo"
                            />

                            <InputError className="mt-2" message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">E-mail</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                placeholder="E-mail"
                            />

                            <InputError className="mt-2" message={errors.email} />
                        </div>

                        {mustVerifyEmail && auth.user.email_verified_at === null && (
                            <div>
                                <p className="mt-2 text-sm text-neutral-800">
                                    Seu e-mail ainda não foi confirmado.
                                    <Link
                                        href={route('verification.send')}
                                        method="post"
                                        as="button"
                                        className="rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2 focus:outline-hidden"
                                    >
                                        Clique aqui para reenviar o e-mail de confirmação.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        Um novo link de confirmação foi enviado para o seu e-mail.
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="tipo_vinculo">Vínculo</Label>
                            <Select
                                value={data.tipo_vinculo}
                                onValueChange={(value) => {
                                    setData('tipo_vinculo', value);

                                    // Trocar de vínculo não deixa uma matrícula do vínculo
                                    // anterior escondida no formulário, pendente de envio.
                                    if (value !== 'aluno_ifpr') setData('matricula_suap', '');
                                    if (value !== 'professor_ifpr') setData('matricula_siape', '');
                                }}
                            >
                                <SelectTrigger id="tipo_vinculo">
                                    <SelectValue placeholder="Selecione seu vínculo com o IFPR" />
                                </SelectTrigger>
                                <SelectContent>
                                    {opcoesVinculo.map((opcao) => (
                                        <SelectItem key={opcao.value} value={opcao.value}>
                                            {opcao.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.tipo_vinculo} />
                        </div>

                        {data.tipo_vinculo === 'aluno_ifpr' && (
                            <div className="grid gap-2">
                                <Label htmlFor="matricula_suap">Matrícula do SUAP</Label>
                                <Input
                                    id="matricula_suap"
                                    value={data.matricula_suap}
                                    onChange={(e) => setData('matricula_suap', e.target.value)}
                                    placeholder="Ex.: 2024104070001"
                                    aria-describedby={errors.matricula_suap ? 'matricula_suap-erro' : undefined}
                                />
                                <InputError id="matricula_suap-erro" message={errors.matricula_suap} />
                            </div>
                        )}

                        {data.tipo_vinculo === 'professor_ifpr' && (
                            <div className="grid gap-2">
                                <Label htmlFor="matricula_siape">Matrícula SIAPE</Label>
                                <Input
                                    id="matricula_siape"
                                    value={data.matricula_siape}
                                    onChange={(e) => setData('matricula_siape', e.target.value)}
                                    placeholder="Ex.: 1234567"
                                    aria-describedby={errors.matricula_siape ? 'matricula_siape-erro' : undefined}
                                />
                                <InputError id="matricula_siape-erro" message={errors.matricula_siape} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="cpf">CPF</Label>
                            <Input
                                id="cpf"
                                value={formatarCpf(data.cpf)}
                                onChange={(e) => setData('cpf', e.target.value.replace(/\D/g, ''))}
                                inputMode="numeric"
                                placeholder="000.000.000-00"
                                maxLength={14}
                                aria-describedby="cpf-ajuda"
                            />
                            <p id="cpf-ajuda" className="text-muted-foreground text-xs">
                                Opcional pra usar o sistema, mas necessário pra emitir certificado com validade legal.
                            </p>
                            <InputError message={errors.cpf} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                {processing ? 'Salvando…' : 'Salvar'}
                            </Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">Salvo.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
