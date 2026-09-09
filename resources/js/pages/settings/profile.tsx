import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { BadgeCheck, CircleAlert, CircleCheck, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import AvatarUpload from '@/components/hackathon/avatar-upload';
import ResumoErro from '@/components/hackathon/resumo-erro';
import SecaoFormulario from '@/components/hackathon/secao-formulario';
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

    const hasRequiredInstitutionalId =
        data.tipo_vinculo === 'externo' ||
        (data.tipo_vinculo === 'aluno_ifpr' && Boolean(data.matricula_suap)) ||
        (data.tipo_vinculo === 'professor_ifpr' && Boolean(data.matricula_siape));
    const profileReadyForCertificate = Boolean(data.name && data.email && data.cpf && data.tipo_vinculo && hasRequiredInstitutionalId);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configurações de perfil" />

            <SettingsLayout>
                <div className="space-y-8">
                    <div
                        className={
                            profileReadyForCertificate
                                ? 'border-primary/20 bg-primary/5 flex items-start gap-4 rounded-2xl border p-5'
                                : 'flex items-start gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-5'
                        }
                        role="status"
                    >
                        <span
                            className={
                                profileReadyForCertificate
                                    ? 'bg-primary text-primary-foreground flex size-10 shrink-0 items-center justify-center rounded-xl'
                                    : 'flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-300'
                            }
                        >
                            {profileReadyForCertificate ? (
                                <CircleCheck className="size-5" aria-hidden="true" />
                            ) : (
                                <CircleAlert className="size-5" aria-hidden="true" />
                            )}
                        </span>
                        <div>
                            <p className="font-semibold">
                                {profileReadyForCertificate ? 'Perfil pronto para o certificado' : 'Complete os dados do certificado'}
                            </p>
                            <p className="text-muted-foreground mt-1 text-sm leading-6">
                                {profileReadyForCertificate
                                    ? 'Nome, CPF e vínculo estão preenchidos. Confira se continuam corretos antes da emissão.'
                                    : 'Preencha CPF, vínculo e a matrícula correspondente para que o documento seja emitido com seus dados completos.'}
                            </p>
                        </div>
                    </div>

                    <SecaoFormulario
                        titulo="Foto de perfil"
                        instrucao="Aparece para colegas de equipe, jurados e organização."
                        className="bg-background"
                    >
                        <AvatarUpload nome={auth.user.name} avatarUrl={auth.user.avatar ?? null} />
                    </SecaoFormulario>

                    <form onSubmit={submit} className="space-y-6" noValidate>
                        <ResumoErro erros={errors} />
                        <SecaoFormulario
                            titulo="Informações do perfil"
                            instrucao="Atualize seu nome, e-mail e vínculo institucional."
                            className="bg-background"
                        >
                            <div className="bg-muted/50 flex items-start gap-3 rounded-xl p-4 text-sm leading-6">
                                <BadgeCheck className="text-primary mt-0.5 size-5 shrink-0" aria-hidden="true" />
                                <p>
                                    Use seu nome completo, como ele deve aparecer no certificado. Os dados institucionais não ficam visíveis no perfil
                                    público.
                                </p>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nome</Label>

                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    autoComplete="name"
                                    placeholder="Nome completo"
                                    className="bg-muted/30 mt-1 h-12 w-full"
                                />

                                <InputError className="mt-2" message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">E-mail</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoComplete="username"
                                    placeholder="E-mail"
                                    className="bg-muted/30 mt-1 h-12 w-full"
                                />

                                <InputError className="mt-2" message={errors.email} />
                            </div>

                            {mustVerifyEmail && auth.user.email_verified_at === null && (
                                <div>
                                    <p className="rounded-xl bg-amber-500/10 p-4 text-sm text-amber-900 dark:text-amber-100">
                                        Seu e-mail ainda não foi confirmado.
                                        <Link
                                            href={route('verification.send')}
                                            method="post"
                                            as="button"
                                            className="focus-visible:ring-ring ml-1 rounded-md font-medium underline underline-offset-4 focus-visible:ring-2 focus-visible:outline-none"
                                        >
                                            Clique aqui para reenviar o e-mail de confirmação.
                                        </Link>
                                    </p>

                                    {status === 'verification-link-sent' && (
                                        <div role="status" className="text-primary mt-2 text-sm font-medium">
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
                                    <SelectTrigger id="tipo_vinculo" className="bg-muted/30 h-12">
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
                                        className="bg-muted/30 h-12"
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
                                        className="bg-muted/30 h-12"
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
                                    className="bg-muted/30 h-12"
                                />
                                <p id="cpf-ajuda" className="text-muted-foreground text-xs">
                                    Opcional para navegar no sistema, mas necessário para emitir o certificado com os dados completos.
                                </p>
                                <InputError message={errors.cpf} />
                            </div>
                            <div className="border-border flex flex-wrap items-center gap-4 border-t pt-5">
                                <Button className="min-h-11" disabled={processing}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                    {processing ? 'Salvando…' : 'Salvar alterações'}
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p role="status" className="text-primary text-sm font-medium">
                                        Alterações salvas.
                                    </p>
                                </Transition>
                            </div>
                        </SecaoFormulario>
                    </form>

                    <DeleteUser />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
