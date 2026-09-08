import EstadoVazio from '@/components/hackathon/estado-vazio';
import PublicLayout from '@/layouts/public-layout';
import { Criterio } from '@/types/rubrica';
import { ClipboardList, Scale } from 'lucide-react';

type Props = { evento: { nome: string } | null; criterios: Criterio[] };

export default function Rubrica({ evento, criterios }: Props) {
    const totalWeight = criterios.reduce((sum, criterion) => sum + criterion.peso, 0);
    return (
        <PublicLayout titulo="Critérios de avaliação" contexto={evento?.nome} descricao="Entenda o que os jurados vão observar no seu projeto.">
            {criterios.length === 0 ? (
                <EstadoVazio
                    icon={ClipboardList}
                    titulo="Rubrica ainda não publicada"
                    descricao="A organização ainda está definindo os critérios de avaliação."
                    acao={{ href: route('regulamento.show'), texto: 'Consultar regulamento' }}
                />
            ) : (
                <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <ol className="grid min-w-0 gap-4">
                        {criterios.map((criterion, index) => {
                            const share = totalWeight > 0 ? (criterion.peso / totalWeight) * 100 : 0;
                            return (
                                <li key={criterion.id} className="border-border bg-card rounded-2xl border p-5 sm:p-8">
                                    <div className="flex items-start gap-4">
                                        <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-xl font-mono text-sm">
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <h2 className="text-xl font-semibold tracking-tight break-words">{criterion.nome}</h2>
                                            {criterion.descricao && (
                                                <p className="text-muted-foreground mt-3 text-sm leading-relaxed break-words">
                                                    {criterion.descricao}
                                                </p>
                                            )}
                                            <dl className="mt-5 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                                                <div>
                                                    <dt className="text-muted-foreground text-xs">Peso</dt>
                                                    <dd className="mt-1 font-semibold">{criterion.peso.toLocaleString('pt-BR')}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-muted-foreground text-xs">Nota máxima</dt>
                                                    <dd className="mt-1 font-semibold">{criterion.nota_maxima.toLocaleString('pt-BR')}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-muted-foreground text-xs">Participação nos pesos</dt>
                                                    <dd className="mt-1 font-semibold">
                                                        {share.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%
                                                    </dd>
                                                </div>
                                            </dl>
                                            <div className="bg-muted mt-5 h-1.5 overflow-hidden rounded-full" aria-hidden="true">
                                                <div className="bg-primary h-full rounded-full" style={{ width: share + '%' }} />
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                    <aside className="bg-secondary rounded-2xl p-6 lg:sticky lg:top-28">
                        <Scale className="text-primary mb-4 size-6" aria-hidden="true" />
                        <h2 className="font-semibold">Como ler a rubrica</h2>
                        <p className="text-muted-foreground mt-3 text-sm leading-relaxed">
                            Cada jurado avalia com estes critérios. A nota da avaliação é a média ponderada pelos pesos abaixo.
                        </p>
                        <p className="text-muted-foreground mt-3 text-sm leading-relaxed">
                            Um peso maior dá mais influência ao critério. Observe também a nota máxima de cada um.
                        </p>
                        <p className="border-border mt-5 border-t pt-4 text-sm">
                            Soma dos pesos: <strong>{totalWeight.toLocaleString('pt-BR')}</strong>
                        </p>
                    </aside>
                </div>
            )}
        </PublicLayout>
    );
}
