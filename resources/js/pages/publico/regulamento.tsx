import { Head } from '@inertiajs/react';
import { Download, GitCommitVertical, ScrollText, Trophy, Users } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import { ArquivoRegulamento, EventoRegulamento } from '@/types/regulamento';

interface Props {
    evento: EventoRegulamento;
    regulamento: ArquivoRegulamento;
}

export default function Regulamento({ evento, regulamento }: Props) {
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Regulamento" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="font-display text-2xl font-semibold tracking-tight">Regulamento</h1>
                    {evento && <p className="text-muted-foreground mt-1 text-sm">{evento.nome}</p>}
                    <p className="text-muted-foreground mt-2 text-sm">
                        Regras definidas antes das inscrições. Valem para todas as equipes, sem exceção.
                    </p>
                </header>

                {regulamento.tem_arquivo && (
                    <a
                        href={route('regulamento.download')}
                        className="border-input bg-background hover:bg-accent flex items-center gap-3 rounded-xl border p-4"
                    >
                        <Download className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        <div className="min-w-0 flex-1">
                            <p className="text-sm font-medium">Baixar PDF do edital</p>
                            {regulamento.atualizado_em && <p className="text-muted-foreground text-xs">Atualizado em {regulamento.atualizado_em}</p>}
                        </div>
                    </a>
                )}

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Trophy className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Critério de desempate
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">Aplicado nesta ordem até sobrar só uma equipe:</p>
                    <ol className="mt-2 flex list-decimal flex-col gap-1 pl-5 text-sm">
                        <li>Maior nota no critério de maior peso da rubrica</li>
                        <li>Maior nota no segundo critério de maior peso</li>
                        <li>Submissão enviada mais cedo</li>
                    </ol>
                    <p className="text-muted-foreground mt-2 text-xs">
                        Empate que sobrevive aos três critérios é empate de verdade: a organização mostra a mesma colocação para as equipes
                        envolvidas.
                    </p>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <GitCommitVertical className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Se o sistema cair no dia
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Vale o horário do último commit no repositório informado pela equipe, feito até o prazo. A submissão no sistema pode ser
                        regularizada depois pela organização.
                    </p>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Users className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Equipes e prazo
                    </h2>
                    {evento ? (
                        <ul className="text-muted-foreground mt-2 flex flex-col gap-1 text-sm">
                            <li>
                                Equipes de {evento.min_team_size} a {evento.max_team_size} pessoas
                            </li>
                            {evento.submission_deadline && <li>Prazo de submissão: {evento.submission_deadline}</li>}
                        </ul>
                    ) : (
                        <p className="text-muted-foreground mt-2 text-sm">Nenhum evento em cartaz no momento.</p>
                    )}
                </section>

                <section className="rounded-xl border border-dashed p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <ScrollText className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Regras específicas desta edição
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Uso de IA, originalidade e o que desclassifica uma submissão estão detalhados no PDF do edital
                        {regulamento.tem_arquivo ? ' acima' : ', quando publicado'}.
                    </p>
                </section>
            </main>
        </div>
    );
}
