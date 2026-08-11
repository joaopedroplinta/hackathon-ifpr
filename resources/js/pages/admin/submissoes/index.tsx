import { Head, Link, router } from '@inertiajs/react';
import { Download, FileText, Inbox, Paperclip, TriangleAlert } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Paginated } from '@/types';
import { FiltrosSubmissao, LinhaSubmissao, OpcoesSubmissao, ResumoSubmissoes } from '@/types/admin';
import { StatusSubmissao } from '@/types/submissao';

interface Props {
    submissoes: Paginated<LinhaSubmissao>;
    filtros: FiltrosSubmissao;
    opcoes: OpcoesSubmissao;
    resumo: ResumoSubmissoes;
}

const seletor =
    'border-input bg-background ring-offset-background focus-visible:ring-ring h-10 rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

/** Estado por texto e cor, nunca só por cor -- .claude/rules/frontend.md. */
const corDoStatus: Record<StatusSubmissao, string> = {
    draft: 'border-muted-foreground/30 text-muted-foreground',
    submitted: 'border-emerald-600/40 text-emerald-700 dark:text-emerald-400',
    late: 'border-amber-600/40 text-amber-700 dark:text-amber-400',
    disqualified: 'border-red-600/40 text-red-700 dark:text-red-400',
};

function formatarData(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString('pt-BR', {
        timeZone: 'America/Sao_Paulo',
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

export default function ListaSubmissoes({ submissoes, filtros, opcoes, resumo }: Props) {
    const [busca, setBusca] = useState(filtros.busca ?? '');
    const [carregando, setCarregando] = useState(false);

    const aplicar = (mudanca: Partial<Record<string, string | number | null>>) => {
        const parametros: Record<string, string> = {};
        const atual = { status: filtros.status, track_id: filtros.track_id, busca, ...mudanca };

        Object.entries(atual).forEach(([chave, valor]) => {
            if (valor !== null && valor !== '' && valor !== undefined) {
                parametros[chave] = String(valor);
            }
        });

        router.get(route('admin.submissions.index'), parametros, {
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

    const temFiltro = filtros.status !== null || filtros.track_id !== null || filtros.busca !== null;

    // Mesmos filtros da tela, pro zip trazer exatamente o que está na lista.
    const parametrosDoFiltro: Record<string, string> = {};
    Object.entries({ status: filtros.status, track_id: filtros.track_id, busca: filtros.busca }).forEach(([chave, valor]) => {
        if (valor !== null) {
            parametrosDoFiltro[chave] = String(valor);
        }
    });

    return (
        <AppLayout breadcrumbs={[{ title: 'Submissões', href: route('admin.submissions.index') }]}>
            <Head title="Submissões" />

            <div className="mx-auto w-full max-w-6xl p-4">
                <header className="mb-6 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">Submissões</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {resumo.total === 1 ? '1 projeto registrado' : `${resumo.total} projetos registrados`} nesta edição.
                        </p>
                    </div>

                    {submissoes.total > 0 && (
                        <Button asChild variant="outline">
                            <a href={route('admin.submissions.export', parametrosDoFiltro)}>
                                <Download className="h-4 w-4" aria-hidden="true" />
                                {temFiltro ? 'Baixar filtrados (.zip)' : 'Baixar tudo (.zip)'}
                            </a>
                        </Button>
                    )}
                </header>

                <section aria-label="Resumo" className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                    {resumo.por_status.map((item) => (
                        <button
                            key={item.valor}
                            type="button"
                            onClick={() => aplicar({ status: filtros.status === item.valor ? null : item.valor })}
                            aria-pressed={filtros.status === item.valor}
                            className={`rounded-xl border p-3 text-left transition ${
                                filtros.status === item.valor ? 'border-primary bg-primary/5' : 'hover:bg-muted/50'
                            }`}
                        >
                            <span className="text-2xl font-semibold">{item.total}</span>
                            <span className="text-muted-foreground block text-xs">{item.rotulo}</span>
                        </button>
                    ))}
                </section>

                {resumo.equipes_sem_envio.length > 0 && (
                    <section
                        aria-label="Equipes sem envio"
                        className="mb-6 rounded-xl border border-amber-600/30 bg-amber-600/10 p-4 text-sm text-amber-900 dark:text-amber-200"
                    >
                        <p className="flex items-center gap-2 font-medium">
                            <TriangleAlert className="h-4 w-4 shrink-0" aria-hidden="true" />
                            {resumo.equipes_sem_envio.length === 1
                                ? '1 equipe ainda não enviou'
                                : `${resumo.equipes_sem_envio.length} equipes ainda não enviaram`}
                        </p>
                        <p className="mt-2 leading-relaxed">{resumo.equipes_sem_envio.join(' · ')}</p>
                    </section>
                )}

                <form onSubmit={buscar} className="mb-4 flex flex-col gap-3 md:flex-row md:items-end">
                    <div className="flex-1">
                        <Label htmlFor="busca">Buscar por equipe ou título</Label>
                        <Input
                            id="busca"
                            value={busca}
                            onChange={(e) => setBusca(e.target.value)}
                            placeholder="Ex.: Alerta de enchente"
                            className="mt-1"
                        />
                    </div>

                    <div>
                        <Label htmlFor="track_id">Trilha</Label>
                        <select
                            id="track_id"
                            value={filtros.track_id ?? ''}
                            onChange={(e) => aplicar({ track_id: e.target.value === '' ? null : e.target.value })}
                            className={`mt-1 w-full md:w-48 ${seletor}`}
                        >
                            <option value="">Todas</option>
                            {opcoes.trilhas.map((trilha) => (
                                <option key={trilha.id} value={trilha.id}>
                                    {trilha.nome}
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
                                aplicar({ status: null, track_id: null, busca: null });
                            }}
                        >
                            Limpar filtros
                        </Button>
                    )}
                </form>

                {submissoes.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <Inbox className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">{temFiltro ? 'Nenhuma submissão com esses filtros' : 'Nenhuma submissão ainda'}</p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {temFiltro
                                ? 'Tente outra trilha ou situação, ou limpe os filtros.'
                                : 'Assim que uma equipe enviar o projeto, ele aparece aqui.'}
                        </p>
                    </div>
                ) : (
                    <div className={`overflow-x-auto rounded-xl border transition-opacity ${carregando ? 'opacity-60' : ''}`}>
                        <table className="w-full min-w-[48rem] text-sm">
                            <caption className="sr-only">Submissões do evento</caption>
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th scope="col" className="p-3 font-medium">
                                        Equipe
                                    </th>
                                    <th scope="col" className="p-3 font-medium">
                                        Projeto
                                    </th>
                                    <th scope="col" className="p-3 font-medium">
                                        Trilha
                                    </th>
                                    <th scope="col" className="p-3 font-medium">
                                        Situação
                                    </th>
                                    <th scope="col" className="p-3 font-medium">
                                        Enviado em
                                    </th>
                                    <th scope="col" className="p-3 font-medium">
                                        Versão
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {submissoes.data.map((linha) => (
                                    <tr key={linha.id} className="hover:bg-muted/30 border-t">
                                        <td className="p-3 font-medium">
                                            <Link href={route('admin.submissions.show', linha.id)} className="hover:underline">
                                                {linha.equipe.nome}
                                            </Link>
                                            {linha.precisa_conferencia && (
                                                <span className="mt-1 block text-xs font-normal text-amber-700 dark:text-amber-400">
                                                    {linha.origem_label} — conferir
                                                </span>
                                            )}
                                        </td>
                                        <td className="text-muted-foreground p-3">
                                            <span className="flex items-center gap-2">
                                                <FileText className="h-4 w-4 shrink-0" aria-hidden="true" />
                                                {linha.titulo ?? 'Sem título'}
                                            </span>
                                            {linha.arquivos > 0 && (
                                                <span className="mt-1 flex items-center gap-1 text-xs">
                                                    <Paperclip className="h-3 w-3" aria-hidden="true" />
                                                    {linha.arquivos === 1 ? '1 arquivo' : `${linha.arquivos} arquivos`}
                                                </span>
                                            )}
                                        </td>
                                        <td className="text-muted-foreground p-3">{linha.trilha?.nome ?? '—'}</td>
                                        <td className="p-3">
                                            <span className={`inline-block rounded-full border px-2 py-0.5 text-xs ${corDoStatus[linha.status]}`}>
                                                {linha.status_label}
                                            </span>
                                        </td>
                                        <td className="text-muted-foreground p-3 whitespace-nowrap">{formatarData(linha.enviado_em)}</td>
                                        <td className="text-muted-foreground p-3">{linha.versao_atual}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {submissoes.last_page > 1 && (
                    <nav aria-label="Paginação" className="mt-4 flex flex-wrap gap-1">
                        {submissoes.links.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    preserveScroll
                                    aria-current={link.active ? 'page' : undefined}
                                    className={`rounded-md border px-3 py-2 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="text-muted-foreground rounded-md border px-3 py-2 text-sm opacity-50"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}
