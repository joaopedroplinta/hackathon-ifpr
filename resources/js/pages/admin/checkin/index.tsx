import { Head, Link, router, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle, MapPin, Search, UserRound } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import LeitorQr from '@/components/hackathon/leitor-qr';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { CheckpointOpcao, ResultadoBusca } from '@/types/checkin';

interface Props {
    checkpoints: CheckpointOpcao[];
    opcoes: { tipos: { valor: string; rotulo: string }[] };
    busca: string | null;
    resultados: ResultadoBusca[];
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

export default function CheckinIndex({ checkpoints, opcoes, busca, resultados }: Props) {
    const [termoBusca, setTermoBusca] = useState(busca ?? '');
    const [buscando, setBuscando] = useState(false);

    const checkpointForm = useForm({ name: '', type: '' });

    const buscar: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('admin.checkin.index'),
            { busca: termoBusca },
            { preserveState: true, onStart: () => setBuscando(true), onFinish: () => setBuscando(false) },
        );
    };

    const criarCheckpoint: FormEventHandler = (e) => {
        e.preventDefault();
        checkpointForm.post(route('admin.checkin.checkpoints.store'), { preserveScroll: true });
    };

    // O QR carrega a URL absoluta gerada pelo crachá (ver CheckinQrCode). Mesma
    // origem vira navegação Inertia; qualquer outra coisa cai pro fallback de
    // navegador puro em vez de travar numa URL que o router não reconhece.
    const lerQrCode = (texto: string) => {
        try {
            const destino = new URL(texto);
            if (destino.origin === window.location.origin) {
                router.visit(destino.pathname + destino.search);
                return;
            }
        } catch {
            // não era uma URL válida -- cai no fallback abaixo
        }

        window.location.href = texto;
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Check-in', href: route('admin.checkin.index') }]}>
            <Head title="Check-in" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <header className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight">Check-in</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Peça pra pessoa mostrar o crachá e escaneie com a câmera do celular. Sem QR? Busque o nome aqui embaixo.
                    </p>
                </header>

                {checkpoints.length === 0 ? (
                    <section className="border-border bg-card rounded-xl border p-6">
                        <h2 className="font-semibold">Nenhum checkpoint cadastrado ainda</h2>
                        <p className="text-muted-foreground mt-1 mb-4 text-sm">
                            Sem um checkpoint, não dá pra confirmar presença nenhuma. Crie o primeiro (ex.: "Entrada").
                        </p>

                        <form onSubmit={criarCheckpoint} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="name">Nome</Label>
                                <Input
                                    id="name"
                                    value={checkpointForm.data.name}
                                    onChange={(e) => checkpointForm.setData('name', e.target.value)}
                                    placeholder="Ex.: Entrada"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Tipo</Label>
                                <select
                                    id="type"
                                    value={checkpointForm.data.type}
                                    onChange={(e) => checkpointForm.setData('type', e.target.value)}
                                    className={`sm:w-40 ${campo}`}
                                >
                                    <option value="">Selecione</option>
                                    {opcoes.tipos.map((tipo) => (
                                        <option key={tipo.valor} value={tipo.valor}>
                                            {tipo.rotulo}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <Button type="submit" disabled={checkpointForm.processing}>
                                {checkpointForm.processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                Criar
                            </Button>
                        </form>
                    </section>
                ) : (
                    <>
                        <section aria-label="Checkpoints" className="mb-6 flex flex-wrap gap-2">
                            {checkpoints.map((c) => (
                                <span key={c.id} className="bg-card flex items-center gap-1.5 rounded-full px-3 py-1 text-xs">
                                    <MapPin className="h-3 w-3 shrink-0" aria-hidden="true" />
                                    {c.nome} · {c.tipo_label}
                                </span>
                            ))}
                        </section>

                        <div className="mb-6">
                            <LeitorQr onDecode={lerQrCode} />
                        </div>

                        <div className="text-muted-foreground mb-6 flex items-center gap-3 text-xs">
                            <span className="bg-border h-px flex-1" />
                            ou busque pelo nome
                            <span className="bg-border h-px flex-1" />
                        </div>

                        <form onSubmit={buscar} className="mb-6 flex gap-3">
                            <div className="flex-1">
                                <Label htmlFor="busca" className="sr-only">
                                    Buscar por nome
                                </Label>
                                <Input
                                    id="busca"
                                    value={termoBusca}
                                    onChange={(e) => setTermoBusca(e.target.value)}
                                    placeholder="Buscar por nome…"
                                    autoFocus
                                />
                            </div>
                            <Button type="submit" disabled={buscando}>
                                <Search className="h-4 w-4" aria-hidden="true" />
                                Buscar
                            </Button>
                        </form>

                        {busca !== null && (
                            <section>
                                {resultados.length === 0 ? (
                                    <p className="text-muted-foreground text-sm">Ninguém inscrito neste evento bate com "{busca}".</p>
                                ) : (
                                    <ul className="border-border bg-card flex flex-col divide-y overflow-hidden rounded-xl border">
                                        {resultados.map((pessoa) => (
                                            <li key={pessoa.id} className="flex items-center justify-between gap-3 p-3">
                                                <div className="flex items-center gap-3">
                                                    <UserRound className="text-muted-foreground h-5 w-5 shrink-0" aria-hidden="true" />
                                                    <div>
                                                        <p className="font-semibold">{pessoa.nome}</p>
                                                        <p className="text-muted-foreground text-xs">{pessoa.email}</p>
                                                    </div>
                                                </div>
                                                {/* Tamanho default, não "sm": é o botão de ação do check-in, no
                                                    celular, em pé -- alvo de toque precisa ser confortável
                                                    (.claude/rules/frontend.md). */}
                                                <Button asChild>
                                                    <Link href={pessoa.confirmar_href}>Confirmar</Link>
                                                </Button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </section>
                        )}
                    </>
                )}
            </motion.div>
        </AppLayout>
    );
}
