import EstadoVazio from '@/components/hackathon/estado-vazio';
import ResumoErro from '@/components/hackathon/resumo-erro';
import Status from '@/components/hackathon/status';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import PublicLayout from '@/layouts/public-layout';
import { SharedData } from '@/types';
import { SubmissaoVitrine } from '@/types/projeto';
import { Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, LoaderCircle, Rocket, Search, Vote } from 'lucide-react';
import { useRef, useState } from 'react';

type Props = {
    evento: { nome: string } | null;
    submissoes: SubmissaoVitrine[];
    votacao_aberta: boolean;
    pode_votar: boolean;
    ja_votou_em: number | null;
};

export default function Projetos({ evento, submissoes, votacao_aberta: votingOpen, pode_votar: canVote, ja_votou_em: votedFor }: Props) {
    const { auth } = usePage<SharedData>().props;
    const [query, setQuery] = useState('');
    const [selected, setSelected] = useState<SubmissaoVitrine | null>(null);
    const [pending, setPending] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const inFlight = useRef(false);
    const voteTrigger = useRef<HTMLButtonElement | null>(null);
    const filtered = submissoes.filter((project) =>
        (project.titulo + ' ' + project.equipe + ' ' + (project.resumo ?? ''))
            .toLocaleLowerCase('pt-BR')
            .includes(query.trim().toLocaleLowerCase('pt-BR')),
    );

    const vote = () => {
        if (!selected || inFlight.current || votedFor !== null || !canVote || !votingOpen) return;
        inFlight.current = true;
        setPending(true);
        setErrors({});
        router.post(
            route('votos.store'),
            { submission_id: selected.id },
            {
                preserveScroll: true,
                onSuccess: () => setSelected(null),
                onError: (responseErrors) => setErrors(responseErrors),
                onFinish: () => {
                    inFlight.current = false;
                    setPending(false);
                },
            },
        );
    };

    return (
        <PublicLayout
            titulo="Projetos"
            contexto={evento?.nome}
            descricao="Ideias que saíram do papel. Explore as soluções construídas pelas equipes."
        >
            <div className="bg-secondary mb-8 flex flex-col justify-between gap-5 rounded-2xl p-5 sm:p-6 lg:flex-row lg:items-center">
                <div className="flex items-start gap-3">
                    <Vote className="text-primary mt-1 size-5 shrink-0" aria-hidden="true" />
                    <div>
                        <h2 className="font-semibold">Votação popular</h2>
                        <p className="text-muted-foreground mt-1 max-w-xl text-sm leading-relaxed">
                            {votedFor !== null
                                ? 'Seu voto foi registrado. Cada pessoa pode votar uma vez, sem troca posterior.'
                                : !votingOpen
                                  ? 'A votação popular não está aberta no momento.'
                                  : canVote
                                    ? 'Você tem um voto. Conheça os projetos antes de escolher; depois da confirmação, não é possível trocar.'
                                    : auth.user
                                      ? 'Você precisa estar inscrito neste evento para votar.'
                                      : 'Entre na sua conta e inscreva-se no evento para votar.'}
                        </p>
                    </div>
                </div>
                {votingOpen && !auth.user && (
                    <Button asChild className="h-11 shrink-0">
                        <Link href={route('login')}>Entrar para participar</Link>
                    </Button>
                )}
            </div>
            {submissoes.length > 0 && (
                <div className="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div className="w-full sm:max-w-md">
                        <label htmlFor="project-search" className="mb-2 block text-sm font-medium">
                            Buscar projeto ou equipe
                        </label>
                        <div className="relative">
                            <Search className="text-muted-foreground pointer-events-none absolute top-3.5 left-3 size-4" aria-hidden="true" />
                            <Input
                                id="project-search"
                                type="search"
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                                placeholder="Nome, equipe ou ideia…"
                                className="h-11 pl-10"
                            />
                        </div>
                    </div>
                    <p role="status" className="text-muted-foreground text-sm">
                        {filtered.length} de {submissoes.length} projetos
                    </p>
                </div>
            )}
            {submissoes.length === 0 ? (
                <EstadoVazio
                    icon={Rocket}
                    titulo="Nenhum projeto enviado ainda"
                    descricao="As entregas das equipes aparecerão aqui quando estiverem disponíveis."
                    acao={{ href: route('agenda.index'), texto: 'Consultar programação' }}
                />
            ) : filtered.length === 0 ? (
                <div>
                    <EstadoVazio icon={Search} titulo="Nenhum projeto encontrado" descricao="Tente outro nome, equipe ou palavra do resumo." />
                    <Button variant="outline" className="mt-4 h-11" onClick={() => setQuery('')}>
                        Limpar busca
                    </Button>
                </div>
            ) : (
                <ul className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    {filtered.map((project) => (
                        <li key={project.id} className="border-border bg-card flex min-w-0 flex-col rounded-2xl border p-6">
                            <div className="mb-5 flex items-center justify-between gap-3">
                                <span className="bg-primary/10 text-primary flex size-10 items-center justify-center rounded-xl">
                                    <Rocket className="size-5" aria-hidden="true" />
                                </span>
                                {votedFor === project.id && <Status tom="sucesso">Seu voto</Status>}
                            </div>
                            <h2 className="text-xl font-semibold tracking-tight break-words">{project.titulo}</h2>
                            <p className="text-muted-foreground mt-2 text-sm break-words">{project.equipe}</p>
                            {project.resumo && <p className="text-muted-foreground mt-4 text-sm leading-relaxed break-words">{project.resumo}</p>}
                            {votingOpen && canVote && votedFor === null && (
                                <div className="mt-auto pt-6">
                                    <Button
                                        variant="outline"
                                        className="h-11 w-full"
                                        disabled={pending}
                                        onClick={(event) => {
                                            voteTrigger.current = event.currentTarget;
                                            setErrors({});
                                            setSelected(project);
                                        }}
                                        aria-label={'Votar em ' + project.titulo}
                                    >
                                        <Vote className="size-4" aria-hidden="true" />
                                        Votar neste projeto
                                    </Button>
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
            <Dialog
                open={selected !== null}
                onOpenChange={(open) => {
                    if (!open && !inFlight.current) setSelected(null);
                }}
            >
                <DialogContent
                    onCloseAutoFocus={(event) => {
                        event.preventDefault();
                        if (voteTrigger.current?.isConnected) voteTrigger.current.focus();
                        else document.getElementById('conteudo-publico')?.focus();
                    }}
                >
                    <DialogTitle>Confirmar seu voto?</DialogTitle>
                    <DialogDescription>
                        Você só pode votar uma vez nesta edição. Após confirmar, não será possível trocar o projeto.
                    </DialogDescription>
                    <div className="bg-muted min-w-0 rounded-xl p-4">
                        <p className="font-semibold break-words">{selected?.titulo}</p>
                        <p className="text-muted-foreground mt-1 text-sm break-words">{selected?.equipe}</p>
                    </div>
                    <ResumoErro titulo="Não foi possível registrar o voto" erros={errors} />
                    <DialogFooter>
                        <Button variant="secondary" className="h-11" disabled={pending} onClick={() => setSelected(null)}>
                            Voltar aos projetos
                        </Button>
                        <Button className="h-11" disabled={pending || !canVote || !votingOpen || votedFor !== null} onClick={vote}>
                            {pending ? (
                                <LoaderCircle className="size-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                            ) : (
                                <CheckCircle2 className="size-4" aria-hidden="true" />
                            )}
                            {pending ? 'Registrando…' : 'Confirmar voto'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </PublicLayout>
    );
}
