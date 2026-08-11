import { Head, router, useForm } from '@inertiajs/react';
import { History, LoaderCircle, RefreshCw, Scale, Shuffle, Trash2, Users } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { LinhaConflito, LinhaJurado, LinhaSubmissaoJurados } from '@/types/jurados';

interface Props {
    submissoes: LinhaSubmissaoJurados[];
    jurados: LinhaJurado[];
    conflitos: LinhaConflito[];
    jurados_por_submissao: number;
    opcoes: { equipes: { id: number; nome: string }[] };
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

export default function JuradosIndex({ submissoes, jurados, conflitos, jurados_por_submissao, opcoes }: Props) {
    const [distribuindo, setDistribuindo] = useState(false);
    const [emAndamento, setEmAndamento] = useState<number | null>(null);
    const [reabrindoId, setReabrindoId] = useState<number | null>(null);

    const configForm = useForm({ judges_per_submission: String(jurados_por_submissao) });
    const atribuirForm = useForm({ judge_id: '', submission_id: '' });
    const conflitoForm = useForm({ judge_id: '', team_id: '', reason: '' });
    const reabrirForm = useForm({ reason: '' });

    const distribuir = () => {
        setDistribuindo(true);
        router.post(route('admin.jurados.distribute'), {}, { preserveScroll: true, onFinish: () => setDistribuindo(false) });
    };

    const salvarConfig: FormEventHandler = (e) => {
        e.preventDefault();
        configForm.patch(route('admin.jurados.config'), { preserveScroll: true });
    };

    const atribuirManual: FormEventHandler = (e) => {
        e.preventDefault();
        atribuirForm.post(route('admin.jurados.store'), { preserveScroll: true, onSuccess: () => atribuirForm.reset() });
    };

    const criarConflito: FormEventHandler = (e) => {
        e.preventDefault();
        conflitoForm.post(route('admin.jurados.conflicts.store'), { preserveScroll: true, onSuccess: () => conflitoForm.reset() });
    };

    const removerAtribuicao = (atribuicaoId: number) => {
        setEmAndamento(atribuicaoId);
        router.delete(route('admin.jurados.destroy', atribuicaoId), { preserveScroll: true, onFinish: () => setEmAndamento(null) });
    };

    const reatribuir = (atribuicaoId: number) => {
        setEmAndamento(atribuicaoId);
        router.post(route('admin.jurados.reassign', atribuicaoId), {}, { preserveScroll: true, onFinish: () => setEmAndamento(null) });
    };

    const removerConflito = (conflitoId: number) => {
        setEmAndamento(conflitoId);
        router.delete(route('admin.jurados.conflicts.destroy', conflitoId), { preserveScroll: true, onFinish: () => setEmAndamento(null) });
    };

    const abrirReabertura = (atribuicaoId: number) => {
        reabrirForm.reset();
        reabrirForm.clearErrors();
        setReabrindoId(atribuicaoId);
    };

    const confirmarReabertura: FormEventHandler = (e) => {
        e.preventDefault();
        if (reabrindoId === null) return;

        reabrirForm.post(route('admin.jurados.reopen-evaluation', reabrindoId), {
            preserveScroll: true,
            onSuccess: () => setReabrindoId(null),
        });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Jurados', href: route('admin.jurados.index') }]}>
            <Head title="Jurados" />

            <div className="mx-auto w-full max-w-4xl p-4">
                <header className="mb-6 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">Jurados</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Distribuição é sugestão — ajuste na mão nunca é sobrescrito por uma nova rodada.
                        </p>
                    </div>

                    <Button onClick={distribuir} disabled={distribuindo}>
                        {distribuindo ? (
                            <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />
                        ) : (
                            <Shuffle className="h-4 w-4" aria-hidden="true" />
                        )}
                        Distribuir automaticamente
                    </Button>
                </header>

                <form onSubmit={salvarConfig} className="mb-6 flex items-end gap-3">
                    <div>
                        <Label htmlFor="judges_per_submission">Jurados por submissão</Label>
                        <Input
                            id="judges_per_submission"
                            type="number"
                            min={1}
                            max={20}
                            value={configForm.data.judges_per_submission}
                            onChange={(e) => configForm.setData('judges_per_submission', e.target.value)}
                            className="w-24"
                        />
                    </div>
                    <Button type="submit" variant="outline" disabled={configForm.processing}>
                        Salvar
                    </Button>
                </form>

                <section aria-label="Jurados cadastrados" className="mb-6 flex flex-wrap gap-2">
                    {jurados.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Nenhum usuário com o papel de jurado ainda.</p>
                    ) : (
                        jurados.map((jurado) => (
                            <span
                                key={jurado.id}
                                className="border-sidebar-border/70 dark:border-sidebar-border flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs"
                            >
                                <Users className="h-3 w-3 shrink-0" aria-hidden="true" />
                                {jurado.nome} · {jurado.total_atribuicoes} {jurado.total_atribuicoes === 1 ? 'submissão' : 'submissões'}
                            </span>
                        ))
                    )}
                </section>

                <section className="border-sidebar-border/70 dark:border-sidebar-border mb-6 rounded-xl border p-4 sm:p-6">
                    <h2 className="font-medium">Atribuir manualmente</h2>
                    <form onSubmit={atribuirManual} className="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="flex-1">
                            <Label htmlFor="submission_id">Submissão</Label>
                            <select
                                id="submission_id"
                                value={atribuirForm.data.submission_id}
                                onChange={(e) => atribuirForm.setData('submission_id', e.target.value)}
                                className={campo}
                            >
                                <option value="">Selecione</option>
                                {submissoes.map((s) => (
                                    <option key={s.id} value={s.id}>
                                        {s.equipe} — {s.titulo}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex-1">
                            <Label htmlFor="judge_id">Jurado</Label>
                            <select
                                id="judge_id"
                                value={atribuirForm.data.judge_id}
                                onChange={(e) => atribuirForm.setData('judge_id', e.target.value)}
                                className={campo}
                            >
                                <option value="">Selecione</option>
                                {jurados.map((j) => (
                                    <option key={j.id} value={j.id}>
                                        {j.nome}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <Button type="submit" disabled={atribuirForm.processing}>
                            Atribuir
                        </Button>
                    </form>
                    {atribuirForm.errors.judge_id && <p className="mt-2 text-sm text-red-600">{atribuirForm.errors.judge_id}</p>}
                </section>

                <h2 className="mb-3 font-medium">Submissões</h2>
                {submissoes.length === 0 ? (
                    <p className="text-muted-foreground mb-6 text-sm">Nenhuma submissão enviada ainda.</p>
                ) : (
                    <ul className="mb-6 flex flex-col gap-3">
                        {submissoes.map((submissao) => (
                            <li key={submissao.id} className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4">
                                <p className="font-medium">{submissao.titulo}</p>
                                <p className="text-muted-foreground text-xs">{submissao.equipe}</p>

                                {submissao.jurados.length === 0 ? (
                                    <p className="text-muted-foreground mt-2 text-sm">Nenhum jurado atribuído ainda.</p>
                                ) : (
                                    <ul className="mt-2 flex flex-wrap gap-2">
                                        {submissao.jurados.map((jurado) => (
                                            <li
                                                key={jurado.atribuicao_id}
                                                className="bg-muted/40 flex items-center gap-2 rounded-full py-1 pr-1 pl-3 text-xs"
                                            >
                                                {jurado.nome}
                                                <span className="text-muted-foreground">· {jurado.status_label}</span>
                                                {jurado.avaliacao_enviada && (
                                                    <button
                                                        type="button"
                                                        onClick={() => abrirReabertura(jurado.atribuicao_id)}
                                                        aria-label={`Reabrir avaliação de ${jurado.nome} para correção`}
                                                        className="hover:bg-muted text-muted-foreground hover:text-foreground rounded-full p-1"
                                                        title="Reabrir avaliação enviada para correção"
                                                    >
                                                        <History className="h-3 w-3" aria-hidden="true" />
                                                    </button>
                                                )}
                                                <button
                                                    type="button"
                                                    onClick={() => reatribuir(jurado.atribuicao_id)}
                                                    disabled={emAndamento === jurado.atribuicao_id}
                                                    aria-label={`Reatribuir vaga de ${jurado.nome}`}
                                                    className="hover:bg-muted text-muted-foreground hover:text-foreground rounded-full p-1"
                                                    title="Jurado ausente: reatribuir a vaga"
                                                >
                                                    <RefreshCw className="h-3 w-3" aria-hidden="true" />
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => removerAtribuicao(jurado.atribuicao_id)}
                                                    disabled={emAndamento === jurado.atribuicao_id}
                                                    aria-label={`Remover ${jurado.nome} desta submissão`}
                                                    className="hover:bg-muted text-muted-foreground hover:text-destructive rounded-full p-1"
                                                >
                                                    <Trash2 className="h-3 w-3" aria-hidden="true" />
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}

                                {submissao.jurados
                                    .filter((jurado) => jurado.atribuicao_id === reabrindoId)
                                    .map((jurado) => (
                                        <form
                                            key={jurado.atribuicao_id}
                                            onSubmit={confirmarReabertura}
                                            className="border-sidebar-border/70 dark:border-sidebar-border mt-3 flex flex-col gap-2 rounded-lg border p-3"
                                        >
                                            <Label htmlFor={`motivo-reabertura-${jurado.atribuicao_id}`}>
                                                Motivo da correção na avaliação de {jurado.nome}
                                            </Label>
                                            <textarea
                                                id={`motivo-reabertura-${jurado.atribuicao_id}`}
                                                value={reabrirForm.data.reason}
                                                onChange={(e) => reabrirForm.setData('reason', e.target.value)}
                                                placeholder="Ex.: Jurado pediu pra revisar a nota do critério de impacto."
                                                rows={2}
                                                className={campo}
                                            />
                                            {reabrirForm.errors.reason && <p className="text-sm text-red-600">{reabrirForm.errors.reason}</p>}
                                            <div className="flex gap-2">
                                                <Button type="submit" size="sm" disabled={reabrirForm.processing}>
                                                    {reabrirForm.processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                                    Confirmar reabertura
                                                </Button>
                                                <Button type="button" size="sm" variant="ghost" onClick={() => setReabrindoId(null)}>
                                                    Cancelar
                                                </Button>
                                            </div>
                                        </form>
                                    ))}
                            </li>
                        ))}
                    </ul>
                )}

                <section className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4 sm:p-6">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Scale className="h-4 w-4 shrink-0" aria-hidden="true" />
                        Conflitos de interesse
                    </h2>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Orientador, parente ou colega de trabalho da equipe. Um conflito registrado bloqueia a atribuição — na distribuição automática
                        e na manual.
                    </p>

                    {conflitos.length > 0 && (
                        <ul className="mt-4 flex flex-col gap-2">
                            {conflitos.map((conflito) => (
                                <li key={conflito.id} className="flex items-center justify-between gap-3 text-sm">
                                    <span>
                                        {conflito.jurado} × {conflito.equipe}
                                        {conflito.motivo && <span className="text-muted-foreground"> — {conflito.motivo}</span>}
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        disabled={emAndamento === conflito.id}
                                        onClick={() => removerConflito(conflito.id)}
                                        aria-label={`Remover conflito entre ${conflito.jurado} e ${conflito.equipe}`}
                                        className="hover:text-destructive shrink-0"
                                    >
                                        <Trash2 className="h-4 w-4" aria-hidden="true" />
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    )}

                    <form onSubmit={criarConflito} className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div className="flex-1">
                            <Label htmlFor="conflito_judge_id">Jurado</Label>
                            <select
                                id="conflito_judge_id"
                                value={conflitoForm.data.judge_id}
                                onChange={(e) => conflitoForm.setData('judge_id', e.target.value)}
                                className={campo}
                            >
                                <option value="">Selecione</option>
                                {jurados.map((j) => (
                                    <option key={j.id} value={j.id}>
                                        {j.nome}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex-1">
                            <Label htmlFor="conflito_team_id">Equipe</Label>
                            <select
                                id="conflito_team_id"
                                value={conflitoForm.data.team_id}
                                onChange={(e) => conflitoForm.setData('team_id', e.target.value)}
                                className={campo}
                            >
                                <option value="">Selecione</option>
                                {opcoes.equipes.map((equipe) => (
                                    <option key={equipe.id} value={equipe.id}>
                                        {equipe.nome}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex-1">
                            <Label htmlFor="reason">Motivo (opcional)</Label>
                            <Input
                                id="reason"
                                value={conflitoForm.data.reason}
                                onChange={(e) => conflitoForm.setData('reason', e.target.value)}
                                placeholder="Ex.: Orientador da equipe"
                            />
                        </div>
                        <Button type="submit" disabled={conflitoForm.processing}>
                            Registrar
                        </Button>
                    </form>
                </section>
            </div>
        </AppLayout>
    );
}
