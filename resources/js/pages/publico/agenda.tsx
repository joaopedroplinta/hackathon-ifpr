import EstadoVazio from '@/components/hackathon/estado-vazio';
import Status from '@/components/hackathon/status';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { EventoPublico, ItemAgenda } from '@/types/publico';
import { CalendarClock, CalendarX2, Download, MapPin, Mic } from 'lucide-react';
import { useEffect, useState } from 'react';

type Props = { evento: EventoPublico | { nome: string } | null; itens: ItemAgenda[] };
const dateKey = (iso: string) =>
    new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Sao_Paulo', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(iso));
const dayLabel = (iso: string) =>
    new Date(iso).toLocaleDateString('pt-BR', { timeZone: 'America/Sao_Paulo', weekday: 'long', day: 'numeric', month: 'long' });
const timeLabel = (iso: string) => new Date(iso).toLocaleTimeString('pt-BR', { timeZone: 'America/Sao_Paulo', hour: '2-digit', minute: '2-digit' });

export default function Agenda({ evento, itens }: Props) {
    const [now, setNow] = useState(() => Date.now());
    const [day, setDay] = useState('');
    const [type, setType] = useState('');
    useEffect(() => {
        const timer = window.setInterval(() => setNow(Date.now()), 30000);
        return () => window.clearInterval(timer);
    }, []);
    const sorted = [...itens].sort((a, b) => new Date(a.inicia_em).getTime() - new Date(b.inicia_em).getTime());
    const days = Array.from(new Map(sorted.map((item) => [dateKey(item.inicia_em), dayLabel(item.inicia_em)])).entries());
    const types = Array.from(new Map(sorted.map((item) => [item.tipo, item.tipo_label])).entries());
    const filtered = sorted.filter((item) => (!day || dateKey(item.inicia_em) === day) && (!type || item.tipo === type));
    const groups = filtered.reduce<Record<string, ItemAgenda[]>>((all, item) => {
        (all[dateKey(item.inicia_em)] ??= []).push(item);
        return all;
    }, {});
    return (
        <PublicLayout
            titulo="Agenda"
            contexto={evento?.nome}
            descricao="Planeje seu dia: oficinas, encontros e momentos importantes do hackathon."
            acao={
                itens.length > 0 && (
                    <Button asChild variant="outline" className="h-11">
                        <a href={route('agenda.ics')}>
                            <Download className="size-4" aria-hidden="true" />
                            Adicionar ao calendário (.ics)
                        </a>
                    </Button>
                )
            }
        >
            {itens.length === 0 ? (
                <EstadoVazio
                    icon={CalendarX2}
                    titulo="Agenda ainda não publicada"
                    descricao="Assim que a organização publicar os horários, eles aparecem aqui."
                    acao={{ href: route('home'), texto: 'Voltar ao evento' }}
                />
            ) : (
                <div className="grid items-start gap-8 lg:grid-cols-[15rem_minmax(0,1fr)]">
                    <aside className="border-border bg-card flex flex-col gap-5 rounded-2xl border p-5 lg:sticky lg:top-28">
                        <h2 className="font-semibold">Sua programação</h2>
                        <div>
                            <label htmlFor="agenda-day" className="mb-2 block text-sm">
                                Dia
                            </label>
                            <select
                                id="agenda-day"
                                value={day}
                                onChange={(e) => setDay(e.target.value)}
                                className="border-input bg-background h-11 w-full min-w-0 rounded-lg border px-3 text-sm"
                            >
                                <option value="">Todos os dias</option>
                                {days.map(([key, label]) => (
                                    <option key={key} value={key}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="agenda-type" className="mb-2 block text-sm">
                                Atividade
                            </label>
                            <select
                                id="agenda-type"
                                value={type}
                                onChange={(e) => setType(e.target.value)}
                                className="border-input bg-background h-11 w-full rounded-lg border px-3 text-sm"
                            >
                                <option value="">Todos os tipos</option>
                                {types.map(([key, label]) => (
                                    <option key={key} value={key}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                        </div>
                        {(day || type) && (
                            <Button
                                variant="ghost"
                                className="h-11"
                                onClick={() => {
                                    setDay('');
                                    setType('');
                                }}
                            >
                                Limpar filtros
                            </Button>
                        )}
                        <p className="text-muted-foreground text-xs leading-relaxed">
                            Horários de Brasília (São Paulo). O arquivo de calendário inclui toda a programação publicada.
                        </p>
                    </aside>
                    <div className="min-w-0 space-y-8">
                        <p role="status" className="text-muted-foreground text-sm">
                            {filtered.length} {filtered.length === 1 ? 'atividade' : 'atividades'}
                        </p>
                        {filtered.length === 0 && (
                            <EstadoVazio
                                icon={CalendarX2}
                                titulo="Nenhuma atividade com estes filtros"
                                descricao="Escolha outro dia ou tipo de atividade."
                            />
                        )}
                        {Object.entries(groups).map(([key, items]) => (
                            <section key={key} aria-labelledby={'day-' + key}>
                                <h2 id={'day-' + key} className="mb-4 text-lg font-semibold capitalize">
                                    {dayLabel(items[0].inicia_em)}
                                </h2>
                                <ol className="border-border ml-2 space-y-5 border-l pl-5 sm:pl-8">
                                    {items.map((item) => {
                                        const live = now >= new Date(item.inicia_em).getTime() && now < new Date(item.termina_em).getTime();
                                        return (
                                            <li
                                                key={item.id}
                                                className={`border-border bg-card before:border-primary before:bg-background relative grid min-w-0 gap-4 rounded-2xl border p-5 before:absolute before:top-7 before:-left-[1.6rem] before:size-3 before:rounded-full before:border-2 sm:grid-cols-[7rem_minmax(0,1fr)] sm:p-6 sm:before:-left-[2.35rem] ${live ? 'border-primary/50 bg-primary/5' : ''}`}
                                            >
                                                <div className="text-sm tabular-nums">
                                                    <time dateTime={item.inicia_em} className="text-lg font-semibold">
                                                        {timeLabel(item.inicia_em)}
                                                    </time>
                                                    <p className="text-muted-foreground mt-1">
                                                        até <time dateTime={item.termina_em}>{timeLabel(item.termina_em)}</time>
                                                    </p>
                                                </div>
                                                <div className="min-w-0">
                                                    <div className="mb-3 flex flex-wrap gap-2">
                                                        <Status tom={item.destaque ? 'atencao' : 'neutro'}>{item.tipo_label}</Status>
                                                        {live && (
                                                            <Status tom="sucesso" icon={CalendarClock}>
                                                                Acontecendo agora
                                                            </Status>
                                                        )}
                                                        {item.trilha && (
                                                            <span className="text-muted-foreground inline-flex items-center gap-2 text-xs">
                                                                <span
                                                                    className="size-2 shrink-0 rounded-full"
                                                                    style={{ backgroundColor: item.trilha.cor ?? 'var(--primary)' }}
                                                                    aria-hidden="true"
                                                                />
                                                                {item.trilha.nome}
                                                            </span>
                                                        )}
                                                    </div>
                                                    <h3 className="text-lg font-semibold break-words">{item.titulo}</h3>
                                                    {item.descricao && (
                                                        <p className="text-muted-foreground mt-2 text-sm leading-relaxed break-words">
                                                            {item.descricao}
                                                        </p>
                                                    )}
                                                    <div className="text-muted-foreground mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                                                        {item.local && (
                                                            <span className="flex min-w-0 items-start gap-2">
                                                                <MapPin className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                                                <span className="break-words">{item.local}</span>
                                                            </span>
                                                        )}
                                                        {item.palestrante && (
                                                            <span className="flex min-w-0 items-start gap-2">
                                                                <Mic className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                                                <span className="break-words">{item.palestrante}</span>
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ol>
                            </section>
                        ))}
                    </div>
                </div>
            )}
        </PublicLayout>
    );
}
