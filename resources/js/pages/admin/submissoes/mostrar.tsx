import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Download, ExternalLink, FileText, History, Paperclip, TriangleAlert } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { ArquivoDetalhe, DetalheSubmissao, VersaoSubmissao } from '@/types/admin';
import { StatusSubmissao } from '@/types/submissao';

interface Props {
    submissao: DetalheSubmissao;
    versoes: VersaoSubmissao[];
    arquivos: ArquivoDetalhe[];
}

const corDoStatus: Record<StatusSubmissao, string> = {
    draft: 'bg-muted text-muted-foreground',
    submitted: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400',
    late: 'bg-amber-500/15 text-amber-700 dark:text-amber-400',
    disqualified: 'bg-red-500/15 text-red-700 dark:text-red-400',
};

// Chave do payload → rótulo em português. Chave fora deste mapa aparece
// como veio, sem string técnica travestida de rótulo -- o payload é gravado
// pelo servidor e não deve conter nada fora desta lista, mas o fallback
// evita que uma versão antiga vire tela quebrada.
const rotuloDoCampo: Record<string, string> = {
    title: 'Título',
    summary: 'Resumo',
    description: 'Descrição',
    repo_url: 'Repositório',
    video_url: 'Vídeo',
    deploy_url: 'Deploy',
    status: 'Situação',
    source: 'Origem',
    submitted_at: 'Enviado em',
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

function formatarValorPayload(chave: string, valor: unknown): string {
    if (valor === null || valor === undefined || valor === '') {
        return '—';
    }

    if (chave === 'submitted_at' && typeof valor === 'string') {
        return formatarData(valor);
    }

    if (typeof valor === 'string' || typeof valor === 'number') {
        return String(valor);
    }

    return '';
}

export default function MostrarSubmissao({ submissao, versoes, arquivos }: Props) {
    const links = [
        { rotulo: 'Repositório', href: submissao.repo_url },
        { rotulo: 'Vídeo', href: submissao.video_url },
        { rotulo: 'Deploy', href: submissao.deploy_url },
    ].filter((link) => link.href);

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Submissões', href: route('admin.submissions.index') },
                { title: submissao.equipe.nome, href: route('admin.submissions.show', submissao.id) },
            ]}
        >
            <Head title={`Submissão — ${submissao.equipe.nome}`} />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-3xl p-4 sm:p-6">
                <header className="mb-6">
                    <p className="text-muted-foreground text-sm">{submissao.trilha?.nome ?? 'Sem trilha'}</p>
                    <h1 className="text-2xl font-bold tracking-tight">{submissao.titulo ?? 'Projeto sem título'}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Equipe {submissao.equipe.nome}</p>

                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <span className={`inline-block rounded-full px-2 py-0.5 text-xs ${corDoStatus[submissao.status]}`}>
                            {submissao.status_label}
                        </span>
                        <span className="text-muted-foreground text-xs">Versão atual: {submissao.versao_atual}</span>
                        <span className="text-muted-foreground text-xs">Enviado em {formatarData(submissao.enviado_em)}</span>
                    </div>

                    {submissao.precisa_conferencia && (
                        <p role="status" className="mt-3 flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400">
                            <TriangleAlert className="h-4 w-4 shrink-0" aria-hidden="true" />
                            {submissao.origem_label} — ainda não conferido.
                        </p>
                    )}
                </header>

                {submissao.resumo && (
                    <section className="mb-6">
                        <h2 className="text-sm font-semibold">Resumo</h2>
                        <p className="text-muted-foreground mt-1 text-sm">{submissao.resumo}</p>
                    </section>
                )}

                {submissao.descricao && (
                    <section className="mb-6">
                        <h2 className="text-sm font-semibold">Descrição</h2>
                        <p className="text-muted-foreground mt-1 text-sm whitespace-pre-line">{submissao.descricao}</p>
                    </section>
                )}

                {links.length > 0 && (
                    <section className="mb-6 flex flex-wrap gap-3">
                        {links.map((link) => (
                            <a
                                key={link.rotulo}
                                href={link.href ?? undefined}
                                target="_blank"
                                rel="noreferrer"
                                className="bg-card hover:bg-muted inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm"
                            >
                                <ExternalLink className="h-4 w-4 shrink-0" aria-hidden="true" />
                                {link.rotulo}
                            </a>
                        ))}
                    </section>
                )}

                <section className="border-border bg-card mb-6 rounded-xl border p-4 sm:p-6">
                    <h2 className="font-semibold">Arquivos</h2>

                    {arquivos.length === 0 ? (
                        <p className="text-muted-foreground mt-2 text-sm">Nenhum arquivo anexado a esta submissão.</p>
                    ) : (
                        <ul className="mt-4 divide-y">
                            {arquivos.map((arquivo) => (
                                <li key={arquivo.id} className="flex items-center gap-3 py-3">
                                    <Paperclip className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-semibold">{arquivo.nome}</p>
                                        <p className="text-muted-foreground text-xs">
                                            {arquivo.tamanho} · versão {arquivo.versao}
                                        </p>
                                    </div>
                                    <a
                                        href={route('submission-files.download', arquivo.id)}
                                        className="text-muted-foreground hover:text-foreground inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md"
                                        aria-label={`Baixar ${arquivo.nome}`}
                                    >
                                        <Download className="h-4 w-4" aria-hidden="true" />
                                    </a>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                <section aria-labelledby="historico-versoes">
                    <h2 id="historico-versoes" className="mb-4 flex items-center gap-2 font-semibold">
                        <History className="h-4 w-4 shrink-0" aria-hidden="true" />
                        Histórico de envios
                    </h2>

                    {versoes.length === 0 ? (
                        <p className="text-muted-foreground text-sm">Ainda não há nenhum envio registrado — só rascunho.</p>
                    ) : (
                        <ol className="flex flex-col gap-4">
                            {versoes.map((versao) => (
                                <li key={versao.versao} className="border-border bg-card rounded-xl border p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div className="flex items-center gap-2 text-sm font-semibold">
                                            <FileText className="h-4 w-4 shrink-0" aria-hidden="true" />
                                            Versão {versao.versao}
                                        </div>
                                        <p className="text-muted-foreground text-xs">
                                            {versao.autor} · {formatarData(versao.criado_em)}
                                        </p>
                                    </div>

                                    <dl className="mt-3 grid gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                                        {Object.entries(versao.payload)
                                            .filter(([chave]) => chave !== 'files')
                                            .map(([chave, valor]) => (
                                                <div key={chave}>
                                                    <dt className="text-muted-foreground text-xs">{rotuloDoCampo[chave] ?? chave}</dt>
                                                    <dd className="truncate">{formatarValorPayload(chave, valor)}</dd>
                                                </div>
                                            ))}
                                    </dl>

                                    {Array.isArray(versao.payload.files) && versao.payload.files.length > 0 && (
                                        <div className="mt-3">
                                            <p className="text-muted-foreground text-xs">Arquivos entregues nesta versão</p>
                                            <ul className="mt-1 flex flex-wrap gap-2">
                                                {(versao.payload.files as Array<{ id: number; original_name: string }>).map((arquivo) => (
                                                    <li key={arquivo.id} className="bg-muted rounded-md px-2 py-1 text-xs">
                                                        {arquivo.original_name}
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ol>
                    )}
                </section>

                <Link href={route('admin.submissions.index')} className="text-muted-foreground mt-6 inline-block text-sm hover:underline">
                    ← Voltar para a lista
                </Link>
            </motion.div>
        </AppLayout>
    );
}
