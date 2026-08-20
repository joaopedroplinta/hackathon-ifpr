import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CircleAlert, FileText, LoaderCircle, Lock, TriangleAlert } from 'lucide-react';
import { FormEventHandler } from 'react';

import ContadorPrazo from '@/components/hackathon/contador-prazo';
import HistoricoEnvios from '@/components/hackathon/historico-envios';
import PainelArquivos from '@/components/hackathon/painel-arquivos';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { ArquivosSubmissao, PrazoSubmissao, Submissao, SubmissaoForm, VersaoEnvio } from '@/types/submissao';

interface Props {
    equipe: { nome: string; slug: string };
    submissao: Submissao | null;
    arquivos: ArquivosSubmissao;
    versoes: VersaoEnvio[];
    prazo: PrazoSubmissao;
    pode_editar: boolean;
    motivo_bloqueio: string | null;
}

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

export default function MinhaSubmissao({ equipe, submissao, arquivos, versoes, prazo, pode_editar, motivo_bloqueio }: Props) {
    const { data, setData, post, processing, errors } = useForm<SubmissaoForm>({
        title: submissao?.title ?? '',
        summary: submissao?.summary ?? '',
        description: submissao?.description ?? '',
        repo_url: submissao?.repo_url ?? '',
        video_url: submissao?.video_url ?? '',
        deploy_url: submissao?.deploy_url ?? '',
    });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('submissions.submit'), { preserveScroll: true });
    };

    const salvarRascunho = () => post(route('submissions.save'), { preserveScroll: true });

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Equipe', href: route('teams.show') },
                { title: 'Projeto', href: route('submissions.show') },
            ]}
        >
            <Head title="Projeto" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <header className="mb-6">
                    <h1 className="font-display text-2xl font-semibold tracking-tight">Projeto da {equipe.nome}</h1>
                    {pode_editar && (
                        <p className="text-muted-foreground mt-1 text-sm">
                            Vocês podem salvar um rascunho quantas vezes quiserem. Cada envio fica guardado como uma versão — nada é sobrescrito.
                        </p>
                    )}
                    <div className="mt-3">
                        <ContadorPrazo prazo={prazo} envioAindaAceito={pode_editar} />
                    </div>
                </header>

                <EstadoDoEnvio submissao={submissao} />

                {!pode_editar && (
                    <div role="alert" className="mb-6 flex items-start gap-3 rounded-xl border p-4 text-sm">
                        <Lock className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                        <p>{motivo_bloqueio ?? 'Este projeto não pode mais ser alterado por aqui.'}</p>
                    </div>
                )}

                {pode_editar ? (
                    <form onSubmit={enviar} className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="title">Título do projeto</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                maxLength={120}
                                autoFocus
                                placeholder="Ex.: Painel de alertas de enchente"
                                aria-describedby={errors.title ? 'title-erro' : undefined}
                            />
                            <InputError id="title-erro" message={errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="summary">Resumo</Label>
                            <textarea
                                id="summary"
                                value={data.summary}
                                onChange={(e) => setData('summary', e.target.value)}
                                rows={3}
                                maxLength={300}
                                placeholder="Em duas ou três frases: que problema vocês resolvem e para quem."
                                className={areaTexto}
                                aria-describedby={errors.summary ? 'summary-erro' : 'summary-ajuda'}
                            />
                            <p id="summary-ajuda" className="text-muted-foreground text-xs">
                                É o primeiro texto que o jurado lê. Máximo de 300 caracteres.
                            </p>
                            <InputError id="summary-erro" message={errors.summary} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="repo_url">Link do repositório</Label>
                            <Input
                                id="repo_url"
                                type="text"
                                inputMode="url"
                                value={data.repo_url}
                                onChange={(e) => setData('repo_url', e.target.value)}
                                maxLength={255}
                                placeholder="https://github.com/equipe/projeto"
                                aria-describedby={errors.repo_url ? 'repo_url-erro' : 'repo_url-ajuda'}
                            />
                            <p id="repo_url-ajuda" className="text-muted-foreground text-xs">
                                Obrigatório no envio: o horário do último commit é o que comprova o trabalho de vocês se a internet cair.
                            </p>
                            <InputError id="repo_url-erro" message={errors.repo_url} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="video_url">Link do vídeo (opcional)</Label>
                            <Input
                                id="video_url"
                                type="text"
                                inputMode="url"
                                value={data.video_url}
                                onChange={(e) => setData('video_url', e.target.value)}
                                maxLength={255}
                                placeholder="https://youtube.com/..."
                                aria-describedby={errors.video_url ? 'video_url-erro' : undefined}
                            />
                            <InputError id="video_url-erro" message={errors.video_url} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="deploy_url">Link do projeto no ar (opcional)</Label>
                            <Input
                                id="deploy_url"
                                type="text"
                                inputMode="url"
                                value={data.deploy_url}
                                onChange={(e) => setData('deploy_url', e.target.value)}
                                maxLength={255}
                                placeholder="https://projeto.exemplo.com"
                                aria-describedby={errors.deploy_url ? 'deploy_url-erro' : undefined}
                            />
                            <InputError id="deploy_url-erro" message={errors.deploy_url} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Descrição (opcional)</Label>
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={6}
                                maxLength={5000}
                                placeholder="Como funciona, o que foi construído no hackathon e o que ficou de fora."
                                className={areaTexto}
                                aria-describedby={errors.description ? 'description-erro' : undefined}
                            />
                            <InputError id="description-erro" message={errors.description} />
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row-reverse sm:justify-start">
                            <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                {processing ? 'Enviando…' : submissao?.foi_enviada ? 'Reenviar projeto' : 'Enviar projeto'}
                            </Button>
                            <Button type="button" variant="outline" disabled={processing} onClick={salvarRascunho} className="w-full sm:w-auto">
                                Salvar rascunho
                            </Button>
                        </div>
                    </form>
                ) : (
                    <ResumoSomenteLeitura submissao={submissao} />
                )}

                {/* Fora do <form> de propósito: formulário dentro de
                    formulário é HTML inválido e o navegador desfaz o
                    aninhamento de um jeito imprevisível. */}
                <div className="mt-8">
                    <PainelArquivos arquivos={arquivos} podeAnexar={pode_editar} />
                </div>

                <div className="mt-8">
                    <HistoricoEnvios versoes={versoes} />
                </div>
            </motion.div>
        </AppLayout>
    );
}

/** Estado atual do envio. Ícone e texto juntos — nunca só cor. */
function EstadoDoEnvio({ submissao }: { submissao: Submissao | null }) {
    if (!submissao || !submissao.foi_enviada) {
        return (
            <div className="mb-6 flex items-start gap-3 rounded-xl border border-dashed p-4 text-sm">
                <FileText className="text-muted-foreground mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                <p>
                    {submissao
                        ? 'Vocês têm um rascunho salvo, mas ele ainda não foi enviado. Só o envio conta para a avaliação.'
                        : 'Nenhum projeto enviado ainda. Preencha o formulário abaixo — dá para salvar rascunho e voltar depois.'}
                </p>
            </div>
        );
    }

    const enviadoEm = submissao.enviado_em
        ? new Date(submissao.enviado_em).toLocaleString('pt-BR', {
              timeZone: 'America/Sao_Paulo',
              dateStyle: 'short',
              timeStyle: 'short',
          })
        : null;

    if (submissao.fora_do_prazo) {
        return (
            <div
                role="status"
                className="mb-6 flex items-start gap-3 rounded-xl border border-amber-600/30 bg-amber-600/10 p-4 text-sm text-amber-900 dark:text-amber-200"
            >
                <TriangleAlert className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                <p>
                    <strong>{submissao.status_label}</strong> em {enviadoEm} (versão {submissao.versao_atual}). O projeto foi registrado e a
                    organização vai avaliar o caso.
                </p>
            </div>
        );
    }

    return (
        <div
            role="status"
            className="mb-6 flex items-start gap-3 rounded-xl border border-green-600/30 bg-green-600/10 p-4 text-sm text-green-900 dark:text-green-200"
        >
            <CircleAlert className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <p>
                <strong>{submissao.status_label}</strong> em {enviadoEm} (versão {submissao.versao_atual}).
            </p>
        </div>
    );
}

function ResumoSomenteLeitura({ submissao }: { submissao: Submissao | null }) {
    if (!submissao) {
        return <p className="text-muted-foreground text-sm">Não há projeto registrado para esta equipe.</p>;
    }

    const campos: Array<{ rotulo: string; valor: string | null; link?: boolean }> = [
        { rotulo: 'Título', valor: submissao.title },
        { rotulo: 'Resumo', valor: submissao.summary },
        { rotulo: 'Repositório', valor: submissao.repo_url, link: true },
        { rotulo: 'Vídeo', valor: submissao.video_url, link: true },
        { rotulo: 'Projeto no ar', valor: submissao.deploy_url, link: true },
        { rotulo: 'Descrição', valor: submissao.description },
    ];

    return (
        <dl className="grid gap-4">
            {campos.map((campo) => (
                <div key={campo.rotulo} className="grid gap-1">
                    <dt className="text-muted-foreground text-xs tracking-wide uppercase">{campo.rotulo}</dt>
                    <dd className="text-sm break-words">
                        {campo.valor ? (
                            campo.link ? (
                                <a href={campo.valor} target="_blank" rel="noreferrer noopener" className="underline underline-offset-4">
                                    {campo.valor}
                                </a>
                            ) : (
                                campo.valor
                            )
                        ) : (
                            <span className="text-muted-foreground">Não informado</span>
                        )}
                    </dd>
                </div>
            ))}
        </dl>
    );
}
