import { Head, router } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Check, Copy, Crown, LogOut, UserMinus } from 'lucide-react';
import { useState } from 'react';

import PainelConvites from '@/components/hackathon/painel-convites';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

type Membro = {
    id: number;
    nome: string;
    email: string;
    avatar: string | null;
    papel: string;
    e_lider: boolean;
    sou_eu: boolean;
    pode_remover: boolean;
    pode_sair: boolean;
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
        slug: string;
        membros: Membro[];
    };
    limites: { minimo: number; maximo: number; atual: number };
    pode_editar: boolean;
    pode_transferir: boolean;
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

function Lideranca({ equipe }: { equipe: Props['equipe'] }) {
    const [destino, setDestino] = useState('');
    const [enviando, setEnviando] = useState(false);
    const candidatos = equipe.membros.filter((m) => !m.e_lider);

    if (candidatos.length === 0) {
        return null;
    }

    const transferir = () => {
        setEnviando(true);
        router.patch(
            route('teams.leadership.update', equipe.slug),
            { membership_id: Number(destino) },
            { onFinish: () => setEnviando(false), preserveScroll: true },
        );
    };

    return (
        <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
            <h2 className="font-medium">Passar a liderança</h2>
            <p className="text-muted-foreground mt-1 text-sm">
                Quem receber a liderança passa a poder convidar, editar e submeter pela equipe. Você continua na equipe como integrante.
            </p>

            <div className="mt-4 flex flex-wrap items-center gap-3">
                <select
                    aria-label="Novo líder"
                    value={destino}
                    onChange={(e) => setDestino(e.target.value)}
                    className="border-input bg-background rounded-md border px-3 py-2 text-sm"
                >
                    <option value="">Selecione um integrante</option>
                    {candidatos.map((m) => (
                        <option key={m.id} value={m.id}>
                            {m.nome}
                        </option>
                    ))}
                </select>
                <Button variant="outline" disabled={!destino || enviando} onClick={transferir}>
                    {enviando ? 'Transferindo…' : 'Transferir liderança'}
                </Button>
            </div>
        </section>
    );
}

export default function MinhaEquipe({ equipe, limites, pode_transferir }: Props) {
    const faltam = limites.minimo - limites.atual;
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Equipe', href: route('teams.show') }]}>
            <Head title={equipe.nome} />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4 sm:p-6">
                <header>
                    <h1 className="font-display text-2xl font-semibold tracking-tight">{equipe.nome}</h1>
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
                                {membro.pode_remover && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        aria-label={`Remover ${membro.nome} da equipe`}
                                        onClick={() =>
                                            router.delete(route('teams.members.remove', membro.id), {
                                                preserveScroll: true,
                                            })
                                        }
                                    >
                                        <UserMinus className="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                </section>

                <PainelConvites />

                {pode_transferir && <Lideranca equipe={equipe} />}

                {(() => {
                    const eu = equipe.membros.find((m) => m.sou_eu);
                    if (!eu?.pode_sair) {
                        return null;
                    }

                    const sozinho = equipe.membros.length === 1;

                    return (
                        <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                            <h2 className="font-medium">Sair da equipe</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {sozinho
                                    ? 'Você é a única pessoa na equipe. Ao sair, ela será desfeita.'
                                    : 'Você poderá entrar em outra equipe depois, enquanto o prazo permitir.'}
                            </p>
                            <Button variant="outline" className="mt-4" onClick={() => router.delete(route('teams.leave', eu.id))}>
                                <LogOut className="h-4 w-4" aria-hidden="true" />
                                Sair da equipe
                            </Button>
                        </section>
                    );
                })()}
            </motion.div>
        </AppLayout>
    );
}
