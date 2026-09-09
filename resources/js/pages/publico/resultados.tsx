import EstadoVazio from '@/components/hackathon/estado-vazio';
import Status from '@/components/hackathon/status';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { LinhaPodio, PremioPopular } from '@/types/resultado-publico';
import { Link } from '@inertiajs/react';
import { History, Trophy, Users } from 'lucide-react';

type Props = {
    publicado: boolean;
    evento: { nome: string; edicao: number } | null;
    podio_geral: LinhaPodio[];
    podio_por_trilha: Record<string, LinhaPodio[]>;
    premio_popular: PremioPopular | null;
};

function Ranking({ linhas }: { linhas: LinhaPodio[] }) {
    return (
        <ol className="grid gap-4">
            {[...linhas]
                .sort((a, b) => a.posicao - b.posicao)
                .map((row, index) => (
                    <li
                        key={index}
                        className={`border-border flex items-start gap-4 rounded-2xl border p-5 sm:p-6 ${row.posicao === 1 ? 'bg-secondary/70' : 'bg-card'}`}
                    >
                        <span
                            className={`flex size-14 shrink-0 items-center justify-center rounded-full border text-xl font-semibold ${row.posicao === 1 ? 'border-primary/25 bg-primary/10 text-primary' : 'border-border bg-muted text-muted-foreground'}`}
                        >
                            {row.posicao}º
                        </span>
                        <div className="min-w-0 flex-1">
                            <h3 className="font-semibold break-words">{row.titulo}</h3>
                            <p className="text-muted-foreground mt-1 text-sm break-words">
                                {row.equipe}
                                {row.trilha && ' · ' + row.trilha}
                            </p>
                            <p className="mt-3 text-sm">
                                <span className="text-muted-foreground">Nota final </span>
                                <strong className="tabular-nums">
                                    {row.nota_final.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </strong>
                            </p>
                        </div>
                    </li>
                ))}
        </ol>
    );
}

export default function Resultados({ publicado, evento, podio_geral, podio_por_trilha, premio_popular }: Props) {
    return (
        <PublicLayout
            titulo="Resultados"
            contexto={evento ? evento.nome + ' · Edição ' + evento.edicao : undefined}
            descricao="Conheça os projetos reconhecidos nesta edição."
            acao={
                <Button asChild variant="outline" className="h-11">
                    <Link href={route('edicoes.index')}>
                        <History className="size-4" aria-hidden="true" />
                        Edições anteriores
                    </Link>
                </Button>
            }
        >
            {!publicado ? (
                <EstadoVazio
                    icon={Trophy}
                    titulo="Resultado ainda não publicado"
                    descricao="A classificação será exibida depois da publicação pela organização."
                    acao={{ href: route('rubrica.show'), texto: 'Entender os critérios' }}
                />
            ) : (
                <div className="space-y-10">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h2 className="text-2xl font-semibold tracking-tight">Destaques da edição</h2>
                        <Status tom="sucesso">Resultado publicado</Status>
                    </div>
                    <section aria-labelledby="podio-geral" className="grid items-start gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
                        <div className="event-art relative overflow-hidden rounded-2xl p-7">
                            <Trophy className="size-8 text-[#d6ecac]" aria-hidden="true" />
                            <h2 id="podio-geral" className="mt-5 text-2xl font-semibold">
                                Pódio geral
                            </h2>
                            <p className="mt-3 text-sm leading-relaxed text-white/80">
                                As melhores colocações da edição, conforme a avaliação dos jurados e as regras de desempate.
                            </p>
                        </div>
                        {podio_geral.length ? <Ranking linhas={podio_geral} /> : <EstadoVazio titulo="Nenhuma submissão pontuada ainda." />}
                    </section>
                    {Object.keys(podio_por_trilha).length > 0 && (
                        <section aria-labelledby="podio-trilhas">
                            <h2 id="podio-trilhas" className="mb-5 text-2xl font-semibold tracking-tight">
                                Pódio por trilha
                            </h2>
                            <div className="grid items-start gap-6 lg:grid-cols-2">
                                {Object.entries(podio_por_trilha).map(([track, rows]) => (
                                    <section key={track}>
                                        <h3 className="mb-3 font-semibold break-words">{track}</h3>
                                        <Ranking linhas={rows} />
                                    </section>
                                ))}
                            </div>
                        </section>
                    )}
                    {premio_popular && (
                        <section aria-labelledby="premio-popular" className="bg-secondary flex flex-col gap-5 rounded-2xl p-6 sm:flex-row sm:p-8">
                            <Users className="text-primary size-8 shrink-0" aria-hidden="true" />
                            <div className="min-w-0">
                                <h2 id="premio-popular" className="text-primary text-sm font-semibold">
                                    Prêmio popular
                                </h2>
                                <p className="mt-2 text-2xl font-semibold tracking-tight break-words">{premio_popular.titulo}</p>
                                <p className="text-muted-foreground mt-2 break-words">
                                    {premio_popular.equipe} · {premio_popular.votos} {premio_popular.votos === 1 ? 'voto' : 'votos'}
                                </p>
                            </div>
                        </section>
                    )}
                </div>
            )}
        </PublicLayout>
    );
}
