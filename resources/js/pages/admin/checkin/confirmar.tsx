import { Head, Link, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CheckCircle2, LoaderCircle, UserRound } from 'lucide-react';
import { FormEventHandler } from 'react';

import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { CheckpointOpcao, ParticipanteCheckin } from '@/types/checkin';

interface Props {
    participante: ParticipanteCheckin;
    checkpoints: CheckpointOpcao[];
    checkpoint_selecionado_id: number | null;
    ja_confirmado: boolean;
    confirmado_em: string | null;
    confirmado_por: string | null;
    via: 'busca' | null;
    confirmar_url: string;
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

function formatarData(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleString('pt-BR', { timeZone: 'America/Sao_Paulo', dateStyle: 'short', timeStyle: 'short' });
}

export default function ConfirmarCheckin({
    participante,
    checkpoints,
    checkpoint_selecionado_id,
    ja_confirmado,
    confirmado_em,
    confirmado_por,
    via,
    confirmar_url,
}: Props) {
    const { data, setData, post, processing } = useForm({
        checkpoint_id: checkpoint_selecionado_id ? String(checkpoint_selecionado_id) : '',
        via: via ?? '',
    });

    const confirmar: FormEventHandler = (e) => {
        e.preventDefault();
        post(confirmar_url);
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Check-in', href: route('admin.checkin.index') }]}>
            <Head title={`Check-in — ${participante.nome}`} />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-sm p-4 sm:p-6">
                <div className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-6 text-center">
                    {participante.avatar_url ? (
                        <img src={participante.avatar_url} alt="" className="h-20 w-20 rounded-full object-cover" />
                    ) : (
                        <div className="bg-muted flex h-20 w-20 items-center justify-center rounded-full">
                            <UserRound className="text-muted-foreground h-10 w-10" aria-hidden="true" />
                        </div>
                    )}

                    <div>
                        <p className="text-lg font-semibold">{participante.nome}</p>
                        <p className="text-muted-foreground text-sm">{participante.email}</p>
                    </div>

                    {checkpoints.length === 0 ? (
                        <p className="text-muted-foreground mt-2 text-sm">
                            Nenhum checkpoint cadastrado.{' '}
                            <Link href={route('admin.checkin.index')} className="underline underline-offset-4">
                                Criar um agora
                            </Link>
                            .
                        </p>
                    ) : ja_confirmado ? (
                        <div role="status" className="mt-2 flex flex-col items-center gap-1 text-emerald-700 dark:text-emerald-400">
                            <CheckCircle2 className="h-8 w-8" aria-hidden="true" />
                            <p className="font-semibold">Presença já confirmada</p>
                            <p className="text-muted-foreground text-xs">
                                {formatarData(confirmado_em)}
                                {confirmado_por && ` · por ${confirmado_por}`}
                            </p>
                        </div>
                    ) : (
                        <form onSubmit={confirmar} className="mt-2 w-full">
                            <div className="grid gap-2 text-left">
                                <Label htmlFor="checkpoint_id">Checkpoint</Label>
                                <select
                                    id="checkpoint_id"
                                    value={data.checkpoint_id}
                                    onChange={(e) => setData('checkpoint_id', e.target.value)}
                                    className={campo}
                                >
                                    {checkpoints.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.nome} · {c.tipo_label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <Button type="submit" disabled={processing} className="mt-4 w-full">
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                {processing ? 'Confirmando…' : 'Confirmar presença'}
                            </Button>
                        </form>
                    )}
                </div>

                <Link href={route('admin.checkin.index')} className="text-muted-foreground mt-4 inline-block text-sm hover:underline">
                    ← Buscar outra pessoa
                </Link>
            </motion.div>
        </AppLayout>
    );
}
