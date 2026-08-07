import { Head, usePage } from '@inertiajs/react';
import { Check, Copy, Crown } from 'lucide-react';
import { useState } from 'react';

import PainelConvites from '@/components/hackathon/painel-convites';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';

type Membro = {
    id: number;
    nome: string;
    email: string;
    avatar: string | null;
    papel: string;
    e_lider: boolean;
};

interface Props {
    equipe: {
        nome: string;
        descricao: string | null;
        codigo_convite: string;
        status: string;
        status_label: string;
        trilha: { id: number; name: string; color: string | null } | null;
        sou_lider: boolean;
        membros: Membro[];
    };
    limites: { minimo: number; maximo: number; atual: number };
    pode_editar: boolean;
}

function CodigoConvite({ codigo }: { codigo: string }) {
    const [copiado, setCopiado] = useState(false);

    const copiar = async () => {
        await navigator.clipboard.writeText(codigo);
        setCopiado(true);
        window.setTimeout(() => setCopiado(false), 2000);
    };

    return (
        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
            <h2 className="font-medium">Código de convite</h2>
            <p className="text-muted-foreground mt-1 text-sm">Quem tiver este código entra na equipe.</p>

            <div className="mt-4 flex flex-wrap items-center gap-3">
                <code className="bg-muted rounded-md px-4 py-2 font-mono text-2xl tracking-[0.3em]">{codigo}</code>
                <Button variant="outline" onClick={copiar} aria-live="polite">
                    {copiado ? (
                        <>
                            <Check className="h-4 w-4" aria-hidden="true" /> Copiado
                        </>
                    ) : (
                        <>
                            <Copy className="h-4 w-4" aria-hidden="true" /> Copiar
                        </>
                    )}
                </Button>
            </div>
        </div>
    );
}

export default function MinhaEquipe({ equipe, limites }: Props) {
    const { flash } = usePage<SharedData>().props;
    const faltam = limites.minimo - limites.atual;

    return (
        <AppLayout breadcrumbs={[{ title: 'Equipe', href: route('teams.show') }]}>
            <Head title={equipe.nome} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                {flash?.sucesso && (
                    <div
                        role="status"
                        className="rounded-xl border border-green-600/30 bg-green-600/10 p-4 text-sm text-green-800 dark:text-green-300"
                    >
                        {flash.sucesso}
                    </div>
                )}

                <header>
                    <h1 className="text-2xl font-semibold">{equipe.nome}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {equipe.trilha ? `Trilha: ${equipe.trilha.name}` : 'Sem trilha definida'} · {equipe.status_label}
                    </p>
                    {equipe.descricao && <p className="mt-3 text-sm">{equipe.descricao}</p>}
                </header>

                <CodigoConvite codigo={equipe.codigo_convite} />

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 className="font-medium">Integrantes</h2>
                        <span className="text-muted-foreground text-sm">
                            {limites.atual} de {limites.maximo}
                        </span>
                    </div>

                    {faltam > 0 && (
                        <p className="mt-2 text-sm text-amber-700 dark:text-amber-400">
                            {faltam === 1 ? 'Falta 1 integrante para atingir o mínimo.' : `Faltam ${faltam} integrantes para atingir o mínimo.`}
                        </p>
                    )}

                    <ul className="mt-4 divide-y">
                        {equipe.membros.map((membro) => (
                            <li key={membro.id} className="flex items-center gap-3 py-3">
                                <div className="bg-muted flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-medium">
                                    {membro.nome
                                        .split(' ')
                                        .slice(0, 2)
                                        .map((parte) => parte[0])
                                        .join('')
                                        .toUpperCase()}
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-medium">{membro.nome}</p>
                                    <p className="text-muted-foreground truncate text-xs">{membro.email}</p>
                                </div>
                                {membro.e_lider && (
                                    <span className="text-muted-foreground flex shrink-0 items-center gap-1 text-xs">
                                        <Crown className="h-3.5 w-3.5" aria-hidden="true" />
                                        Líder
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>

                <PainelConvites />
            </div>
        </AppLayout>
    );
}
