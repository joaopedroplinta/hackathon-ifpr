import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, Mail } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SharedData } from '@/types';

type ConvitePendente = {
    id: number;
    email: string;
    expira_em: string;
    expirado: boolean;
};

type ConvitesProps = {
    convites: {
        pode_convidar: boolean;
        motivo_bloqueio: string | null;
        pendentes: ConvitePendente[];
    };
};

// `type`, não `interface`: o useForm do Inertia v2 exige a index signature
// implícita que só o `type` ganha -- ver CLAUDE.md.
type ConviteForm = { email: string };

/**
 * Lê os próprios dados via usePage em vez de receber por props: assim
 * `equipe/minha.tsx` só precisa do import e de uma linha de render, sem
 * mexer na assinatura da função -- outra pessoa está editando aquele
 * arquivo em paralelo.
 */
export default function PainelConvites() {
    const { convites } = usePage<SharedData & ConvitesProps>().props;
    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm<ConviteForm>({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('team-invites.store'), {
            preserveScroll: true,
            onSuccess: () => reset('email'),
        });
    };

    return (
        <section className="border-border bg-card rounded-xl border p-6">
            <h2 className="font-semibold">Convidar por e-mail</h2>
            <p className="text-muted-foreground mt-1 text-sm">Manda um link de convite para quem ainda não tem o código da equipe.</p>

            {convites.pode_convidar ? (
                <form onSubmit={submit} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="convite-email" className="sr-only">
                            E-mail
                        </Label>
                        <Input
                            id="convite-email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="nome@exemplo.com"
                            required
                            aria-describedby={errors.email ? 'convite-email-erro' : undefined}
                        />
                        <InputError id="convite-email-erro" message={errors.email} />
                    </div>
                    <Button type="submit" disabled={processing} className="shrink-0">
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                        {processing ? 'Enviando…' : 'Convidar'}
                    </Button>
                </form>
            ) : (
                <p className="text-muted-foreground mt-3 text-sm">{convites.motivo_bloqueio ?? 'Você não pode convidar agora.'}</p>
            )}

            {recentlySuccessful && <p className="mt-2 text-sm text-green-700 dark:text-green-400">Convite enviado.</p>}

            <div className="mt-6">
                <h3 className="text-sm font-semibold">Convites pendentes</h3>

                {convites.pendentes.length === 0 ? (
                    <p className="text-muted-foreground mt-2 text-sm">Nenhum convite pendente no momento.</p>
                ) : (
                    <ul className="mt-3 divide-y">
                        {convites.pendentes.map((convite) => (
                            <li key={convite.id} className="flex items-center gap-3 py-3">
                                <Mail className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold">{convite.email}</p>
                                    <p className="text-muted-foreground text-xs">
                                        {convite.expirado ? 'Expirado' : `Expira em ${new Date(convite.expira_em).toLocaleDateString('pt-BR')}`}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </section>
    );
}
