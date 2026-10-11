import { Head, Link, router, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Inbox, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Paginated } from '@/types';
import { FiltrosEquipe, LinhaEquipe, OpcoesEquipe, StatusEquipe } from '@/types/admin';

interface Props {
    equipes: Paginated<LinhaEquipe>;
    filtros: FiltrosEquipe;
    opcoes: OpcoesEquipe;
}

type FormMotivo = { motivo: string };
type Acao = { equipe: LinhaEquipe; tipo: 'desclassificar' | 'reabilitar' };

const seletor =
    'border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

/** Estado por texto e cor, nunca só por cor -- .claude/rules/frontend.md. */
const corDoStatus: Record<StatusEquipe, string> = {
    draft: 'bg-muted text-muted-foreground',
    confirmed: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    disqualified: 'bg-red-500/15 text-red-700 dark:text-red-400',
};

export default function ListaEquipes({ equipes, filtros, opcoes }: Props) {
    const [busca, setBusca] = useState(filtros.busca ?? '');
    const [carregando, setCarregando] = useState(false);
    const [acao, setAcao] = useState<Acao | null>(null);
    const form = useForm<FormMotivo>({ motivo: '' });

    const aplicar = (mudanca: Partial<Record<string, string | null>>) => {
        const parametros: Record<string, string> = {};
        const atual = { status: filtros.status, busca, ...mudanca };

        Object.entries(atual).forEach(([chave, valor]) => {
            if (valor !== null && valor !== '' && valor !== undefined) {
                parametros[chave] = String(valor);
            }
        });

        router.get(route('painel.teams.index'), parametros, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setCarregando(true),
            onFinish: () => setCarregando(false),
        });
    };

    const buscar: FormEventHandler = (e) => {
        e.preventDefault();
        aplicar({ busca });
    };

    const fechar = () => {
        setAcao(null);
        form.reset('motivo');
        form.clearErrors();
    };

    const confirmar: FormEventHandler = (e) => {
        e.preventDefault();

        if (!acao) {
            return;
        }

        const rota = acao.tipo === 'desclassificar' ? 'painel.teams.disqualify' : 'painel.teams.reinstate';

        form.post(route(rota, acao.equipe.id), { preserveScroll: true, onSuccess: fechar });
    };

    const temFiltro = filtros.status !== null || filtros.busca !== null;
    const desclassificando = acao?.tipo === 'desclassificar';

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Equipes', href: route('painel.teams.index') }]}>
            <Head title="Equipes" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-7xl p-4 sm:p-6 lg:p-8">
                <header className="border-border/70 mb-8 border-b pb-6">
                    <h1 className="text-3xl font-medium tracking-[-0.03em]">Equipes</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {equipes.total === 1 ? '1 equipe' : `${equipes.total} equipes`} nesta edição. Desclassifique ou reabilite por aqui, mesmo
                        quando a equipe ainda não enviou nada.
                    </p>
                </header>

                <form
                    onSubmit={buscar}
                    className="border-border/70 bg-card/80 mb-4 flex flex-col gap-3 rounded-3xl border p-4 shadow-sm md:flex-row md:items-end"
                >
                    <div className="flex-1">
                        <Label htmlFor="busca">Buscar por nome da equipe</Label>
                        <Input id="busca" value={busca} onChange={(e) => setBusca(e.target.value)} placeholder="Ex.: Os Devs" className="mt-1" />
                    </div>

                    <div>
                        <Label htmlFor="status">Situação</Label>
                        <select
                            id="status"
                            value={filtros.status ?? ''}
                            onChange={(e) => aplicar({ status: e.target.value === '' ? null : e.target.value })}
                            className={`mt-1 w-full md:w-48 ${seletor}`}
                        >
                            <option value="">Todas</option>
                            {opcoes.status.map((opcao) => (
                                <option key={opcao.valor} value={opcao.valor}>
                                    {opcao.rotulo}
                                </option>
                            ))}
                        </select>
                    </div>

                    <Button type="submit" disabled={carregando} className="h-10">
                        {carregando ? 'Buscando…' : 'Buscar'}
                    </Button>

                    {temFiltro && (
                        <Button
                            type="button"
                            variant="ghost"
                            className="h-10"
                            onClick={() => {
                                setBusca('');
                                aplicar({ status: null, busca: null });
                            }}
                        >
                            Limpar filtros
                        </Button>
                    )}
                </form>

                {equipes.data.length === 0 ? (
                    <div className="border-border/70 bg-card/80 flex flex-col items-center gap-3 rounded-3xl border p-10 text-center shadow-sm">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Inbox className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">{temFiltro ? 'Nenhuma equipe com esses filtros' : 'Nenhuma equipe ainda'}</p>
                        <p className="text-muted-foreground text-sm">
                            {temFiltro ? 'Tente outra situação ou limpe os filtros.' : 'Assim que alguém criar uma equipe, ela aparece aqui.'}
                        </p>
                    </div>
                ) : (
                    <div
                        className={`border-border/70 bg-card/80 overflow-x-auto rounded-3xl border shadow-sm transition-opacity ${carregando ? 'opacity-60' : ''}`}
                    >
                        <table className="w-full min-w-[52rem] text-sm">
                            <caption className="sr-only">Equipes do evento</caption>
                            <thead className="bg-muted/40 text-left">
                                <tr>
                                    <th scope="col" className="p-3 text-xs font-medium">
                                        Equipe
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-medium">
                                        Trilha
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-medium">
                                        Líder
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-medium">
                                        Integrantes
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-medium">
                                        Situação
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-medium">
                                        Projeto
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-medium">
                                        <span className="sr-only">Ações</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {equipes.data.map((equipe) => (
                                    <tr key={equipe.id} className="hover:bg-muted/40 border-border border-t transition-colors">
                                        <td className="p-3 font-semibold">{equipe.nome}</td>
                                        <td className="text-muted-foreground p-3">{equipe.trilha?.nome ?? '—'}</td>
                                        <td className="text-muted-foreground p-3">{equipe.lider ?? '—'}</td>
                                        <td className="text-muted-foreground p-3">{equipe.integrantes}</td>
                                        <td className="p-3">
                                            <span className={`inline-block rounded-full px-2 py-0.5 text-xs ${corDoStatus[equipe.status]}`}>
                                                {equipe.status_label}
                                            </span>
                                        </td>
                                        <td className="text-muted-foreground p-3">
                                            {equipe.submissao ? (
                                                <Link href={route('painel.submissions.show', equipe.submissao.id)} className="hover:underline">
                                                    {equipe.submissao.titulo ?? 'Sem título'}
                                                    <span className="block text-xs">{equipe.submissao.status_label}</span>
                                                </Link>
                                            ) : (
                                                'Sem envio'
                                            )}
                                        </td>
                                        <td className="p-3 text-right">
                                            {equipe.pode_desclassificar && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setAcao({ equipe, tipo: 'desclassificar' })}
                                                    aria-label={`Desclassificar a equipe ${equipe.nome}`}
                                                >
                                                    Desclassificar
                                                </Button>
                                            )}
                                            {equipe.pode_reabilitar && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setAcao({ equipe, tipo: 'reabilitar' })}
                                                    aria-label={`Reabilitar a equipe ${equipe.nome}`}
                                                >
                                                    Reabilitar
                                                </Button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {equipes.last_page > 1 && (
                    <nav aria-label="Paginação" className="mt-4 flex flex-wrap gap-1">
                        {equipes.links.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    preserveScroll
                                    aria-current={link.active ? 'page' : undefined}
                                    className={`rounded-full px-3 py-2 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'bg-card hover:bg-muted'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="text-muted-foreground bg-card rounded-full px-3 py-2 text-sm opacity-50"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </nav>
                )}

                <Dialog open={acao !== null} onOpenChange={(aberto) => !aberto && fechar()}>
                    <DialogContent>
                        <form onSubmit={confirmar} className="flex flex-col gap-4">
                            <DialogTitle>{desclassificando ? 'Desclassificar equipe' : 'Reabilitar equipe'}</DialogTitle>
                            <DialogDescription>
                                {desclassificando
                                    ? `A equipe ${acao?.equipe.nome} sai da avaliação, do ranking, da vitrine e da votação e não envia mais. Os integrantes recebem um e-mail com o motivo. Pode ser desfeito depois.`
                                    : `A equipe ${acao?.equipe.nome} volta à situação anterior à desclassificação. Os integrantes recebem um e-mail com o motivo.`}
                            </DialogDescription>

                            <div className="grid gap-2">
                                <Label htmlFor="motivo-equipe">Motivo (a equipe vai ler)</Label>
                                <textarea
                                    id="motivo-equipe"
                                    rows={4}
                                    value={form.data.motivo}
                                    onChange={(e) => form.setData('motivo', e.target.value)}
                                    className="border-input bg-background ring-offset-background focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                />
                                <InputError message={form.errors.motivo} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                </DialogClose>
                                <Button type="submit" variant={desclassificando ? 'destructive' : 'default'} disabled={form.processing}>
                                    {form.processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                    {desclassificando ? 'Desclassificar' : 'Reabilitar'}
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </motion.div>
        </AppLayout>
    );
}
