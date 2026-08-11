import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { ItemAgendaExistente, ItemAgendaForm, OpcoesFormularioAgenda } from '@/types/agenda-admin';

interface Props {
    item: ItemAgendaExistente | null;
    opcoes: OpcoesFormularioAgenda;
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

/**
 * Brasil não tem mais horário de verão desde 2019 -- América/São_Paulo é
 * -03:00 o ano inteiro. Fixar o offset evita depender do fuso do sistema
 * operacional de quem preenche o formulário, que nem sempre está certo.
 */
function paraIsoDeSaoPaulo(valorDatetimeLocal: string): string {
    return new Date(`${valorDatetimeLocal}:00-03:00`).toISOString();
}

function deIsoParaDatetimeLocal(iso: string): string {
    return new Date(iso).toLocaleString('sv-SE', { timeZone: 'America/Sao_Paulo' }).slice(0, 16).replace(' ', 'T');
}

export default function FormularioAgenda({ item, opcoes }: Props) {
    const editando = item !== null;

    const { data, setData, transform, post, patch, processing, errors } = useForm<ItemAgendaForm>({
        title: item?.title ?? '',
        description: item?.description ?? '',
        type: item?.type ?? '',
        starts_at: item ? deIsoParaDatetimeLocal(item.starts_at) : '',
        ends_at: item ? deIsoParaDatetimeLocal(item.ends_at) : '',
        location: item?.location ?? '',
        speaker_name: item?.speaker_name ?? '',
        speaker_bio: item?.speaker_bio ?? '',
        track_id: item?.track_id ? String(item.track_id) : '',
    });

    // transform() só muda o que vai pro servidor -- o campo continua
    // mostrando "2026-08-20T09:00" pro organizador, sem fuso no meio.
    transform((dadosAtuais) => ({
        ...dadosAtuais,
        starts_at: paraIsoDeSaoPaulo(dadosAtuais.starts_at),
        ends_at: paraIsoDeSaoPaulo(dadosAtuais.ends_at),
    }));

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();

        if (editando) {
            patch(route('admin.agenda.update', item.id));
        } else {
            post(route('admin.agenda.store'));
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Agenda', href: route('admin.agenda.index') },
                { title: editando ? 'Editar item' : 'Novo item', href: '#' },
            ]}
        >
            <Head title={editando ? 'Editar item da agenda' : 'Novo item da agenda'} />

            <div className="mx-auto w-full max-w-2xl p-4">
                <h1 className="mb-6 text-2xl font-semibold">{editando ? 'Editar item' : 'Novo item da agenda'}</h1>

                <form onSubmit={enviar} className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Título</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            maxLength={120}
                            autoFocus
                            placeholder="Ex.: Abertura e boas-vindas"
                            aria-describedby={errors.title ? 'title-erro' : undefined}
                        />
                        <InputError id="title-erro" message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="type">Tipo</Label>
                        <select
                            id="type"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value as ItemAgendaForm['type'])}
                            className={campo}
                            aria-describedby={errors.type ? 'type-erro' : undefined}
                        >
                            <option value="">Selecione</option>
                            {opcoes.tipos.map((tipo) => (
                                <option key={tipo.valor} value={tipo.valor}>
                                    {tipo.rotulo}
                                </option>
                            ))}
                        </select>
                        <InputError id="type-erro" message={errors.type} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="starts_at">Início</Label>
                            <Input
                                id="starts_at"
                                type="datetime-local"
                                value={data.starts_at}
                                onChange={(e) => setData('starts_at', e.target.value)}
                                aria-describedby={errors.starts_at ? 'starts_at-erro' : undefined}
                            />
                            <InputError id="starts_at-erro" message={errors.starts_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="ends_at">Término</Label>
                            <Input
                                id="ends_at"
                                type="datetime-local"
                                value={data.ends_at}
                                onChange={(e) => setData('ends_at', e.target.value)}
                                aria-describedby={errors.ends_at ? 'ends_at-erro' : undefined}
                            />
                            <InputError id="ends_at-erro" message={errors.ends_at} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="location">Local (opcional)</Label>
                        <Input
                            id="location"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            maxLength={120}
                            placeholder="Ex.: Auditório"
                            aria-describedby={errors.location ? 'location-erro' : undefined}
                        />
                        <InputError id="location-erro" message={errors.location} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="track_id">Trilha (opcional)</Label>
                        <select id="track_id" value={data.track_id} onChange={(e) => setData('track_id', e.target.value)} className={campo}>
                            <option value="">Nenhuma</option>
                            {opcoes.trilhas.map((trilha) => (
                                <option key={trilha.id} value={trilha.id}>
                                    {trilha.nome}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="speaker_name">Palestrante (opcional)</Label>
                        <Input
                            id="speaker_name"
                            value={data.speaker_name}
                            onChange={(e) => setData('speaker_name', e.target.value)}
                            maxLength={120}
                            aria-describedby={errors.speaker_name ? 'speaker_name-erro' : undefined}
                        />
                        <InputError id="speaker_name-erro" message={errors.speaker_name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="speaker_bio">Bio do palestrante (opcional)</Label>
                        <textarea
                            id="speaker_bio"
                            value={data.speaker_bio}
                            onChange={(e) => setData('speaker_bio', e.target.value)}
                            rows={2}
                            className={areaTexto}
                            aria-describedby={errors.speaker_bio ? 'speaker_bio-erro' : undefined}
                        />
                        <InputError id="speaker_bio-erro" message={errors.speaker_bio} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Descrição (opcional)</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            className={areaTexto}
                            aria-describedby={errors.description ? 'description-erro' : undefined}
                        />
                        <InputError id="description-erro" message={errors.description} />
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row-reverse sm:justify-start">
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            {processing ? 'Salvando…' : editando ? 'Salvar alterações' : 'Criar item'}
                        </Button>
                    </div>

                    <p className="text-muted-foreground text-xs">
                        O item {editando ? 'continua' : 'entra'} como rascunho{editando ? ', se já não estiver publicado, ' : ' '}até você publicar na
                        lista.
                    </p>
                </form>
            </div>
        </AppLayout>
    );
}
