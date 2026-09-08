import EstadoVazio from '@/components/hackathon/estado-vazio';
import Status from '@/components/hackathon/status';
import PublicLayout from '@/layouts/public-layout';
import { Edicao } from '@/types/edicao';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, History } from 'lucide-react';

export default function Edicoes({ edicoes }: { edicoes: Edicao[] }) {
    return (
        <PublicLayout titulo="Edições anteriores" descricao="Conheça os encontros que já aconteceram e os projetos que se destacaram.">
            {edicoes.length === 0 ? (
                <EstadoVazio
                    icon={History}
                    titulo="Nenhuma edição encerrada ainda"
                    descricao="Quando uma edição terminar e o resultado for publicado, ela aparece aqui."
                    acao={{ href: route('home'), texto: 'Ver evento atual' }}
                />
            ) : (
                <ul className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {edicoes.map((edition) => (
                        <li key={edition.slug} className="min-w-0">
                            <Link
                                href={route('resultados.show.edicao', edition.slug)}
                                className="group border-border bg-card hover:border-primary/50 flex h-full flex-col rounded-2xl border p-6 transition-colors sm:p-8"
                            >
                                <div className="mb-8 flex flex-wrap items-center justify-between gap-3">
                                    <span className="text-primary text-4xl font-semibold tracking-tight">
                                        {String(edition.edicao).padStart(2, '0')}
                                    </span>
                                    <Status tom="sucesso">Resultados publicados</Status>
                                </div>
                                <h2 className="text-xl font-semibold tracking-tight break-words">{edition.nome}</h2>
                                <p className="text-muted-foreground mt-3 text-sm">
                                    {edition.encerrado_em
                                        ? 'Encerrada em ' +
                                          new Date(edition.encerrado_em).toLocaleDateString('pt-BR', {
                                              day: 'numeric',
                                              month: 'long',
                                              year: 'numeric',
                                              timeZone: 'America/Sao_Paulo',
                                          })
                                        : 'Edição encerrada'}
                                </p>
                                <span className="text-primary mt-auto flex items-center justify-between gap-3 pt-8 text-sm font-semibold">
                                    Explorar resultados
                                    <ArrowUpRight className="size-5 shrink-0" aria-hidden="true" />
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </PublicLayout>
    );
}
