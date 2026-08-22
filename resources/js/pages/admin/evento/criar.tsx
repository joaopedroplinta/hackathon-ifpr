import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { NovoEventoForm } from '@/types/evento-admin';

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

/**
 * Brasil não tem mais horário de verão desde 2019 -- América/São_Paulo é
 * -03:00 o ano inteiro. Mesmo helper de admin/evento/editar.tsx, mas seguro
 * pra campo vazio: nem toda janela do evento é obrigatória.
 */
function paraIsoDeSaoPaulo(valorDatetimeLocal: string): string | null {
    if (!valorDatetimeLocal) {
        return null;
    }

    return new Date(`${valorDatetimeLocal}:00-03:00`).toISOString();
}

export default function CriarEvento() {
    const { data, setData, transform, post, processing, errors } = useForm<NovoEventoForm>({
        name: '',
        description: '',
        registration_opens_at: '',
        registration_closes_at: '',
        starts_at: '',
        ends_at: '',
        submission_deadline: '',
        voting_opens_at: '',
        voting_closes_at: '',
        min_team_size: '1',
        max_team_size: '5',
        regulamento: null,
    });

    transform((dadosAtuais) => ({
        ...dadosAtuais,
        registration_opens_at: paraIsoDeSaoPaulo(dadosAtuais.registration_opens_at),
        registration_closes_at: paraIsoDeSaoPaulo(dadosAtuais.registration_closes_at),
        starts_at: paraIsoDeSaoPaulo(dadosAtuais.starts_at),
        ends_at: paraIsoDeSaoPaulo(dadosAtuais.ends_at),
        submission_deadline: paraIsoDeSaoPaulo(dadosAtuais.submission_deadline),
        voting_opens_at: paraIsoDeSaoPaulo(dadosAtuais.voting_opens_at),
        voting_closes_at: paraIsoDeSaoPaulo(dadosAtuais.voting_closes_at),
    }));

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.evento.store'), { preserveScroll: true, forceFormData: true });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Evento', href: route('admin.evento.create') }]}>
            <Head title="Criar evento" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <h1 className="mb-1 text-2xl font-bold tracking-tight">Criar evento</h1>
                <p className="text-muted-foreground mb-6 text-sm">
                    Ainda não existe nenhuma edição do hackathon cadastrada. Preencha o essencial agora — as demais telas do painel (agenda, jurados,
                    avaliação) só ficam disponíveis depois que o primeiro evento existir. Você pode ajustar tudo de novo em Editar evento.
                </p>

                <form onSubmit={enviar} className="border-border bg-card grid gap-6 rounded-xl border p-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nome do evento</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="Ex.: Hackathon IFPR Pinhais 2026"
                            maxLength={120}
                            aria-describedby={errors.name ? 'name-erro' : undefined}
                        />
                        <InputError id="name-erro" message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Tema / desafio</Label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            rows={4}
                            placeholder="Ex.: soluções inovadoras para mobilidade urbana"
                            className={areaTexto}
                            aria-describedby={errors.description ? 'description-erro' : undefined}
                        />
                        <InputError id="description-erro" message={errors.description} />
                    </div>

                    <fieldset className="grid gap-4 sm:grid-cols-2">
                        <legend className="mb-1 text-sm font-semibold">Inscrições</legend>
                        <div className="grid gap-2">
                            <Label htmlFor="registration_opens_at">Abre em</Label>
                            <Input
                                id="registration_opens_at"
                                type="datetime-local"
                                value={data.registration_opens_at}
                                onChange={(e) => setData('registration_opens_at', e.target.value)}
                                aria-describedby={errors.registration_opens_at ? 'registration_opens_at-erro' : undefined}
                            />
                            <InputError id="registration_opens_at-erro" message={errors.registration_opens_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="registration_closes_at">Fecha em</Label>
                            <Input
                                id="registration_closes_at"
                                type="datetime-local"
                                value={data.registration_closes_at}
                                onChange={(e) => setData('registration_closes_at', e.target.value)}
                                aria-describedby={errors.registration_closes_at ? 'registration_closes_at-erro' : undefined}
                            />
                            <InputError id="registration_closes_at-erro" message={errors.registration_closes_at} />
                        </div>
                    </fieldset>

                    <fieldset className="grid gap-4 sm:grid-cols-2">
                        <legend className="mb-1 text-sm font-semibold">Evento</legend>
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
                            <Label htmlFor="ends_at">Fim</Label>
                            <Input
                                id="ends_at"
                                type="datetime-local"
                                value={data.ends_at}
                                onChange={(e) => setData('ends_at', e.target.value)}
                                aria-describedby={errors.ends_at ? 'ends_at-erro' : undefined}
                            />
                            <InputError id="ends_at-erro" message={errors.ends_at} />
                        </div>
                    </fieldset>

                    <div className="grid gap-2">
                        <Label htmlFor="submission_deadline">Prazo de submissão</Label>
                        <Input
                            id="submission_deadline"
                            type="datetime-local"
                            value={data.submission_deadline}
                            onChange={(e) => setData('submission_deadline', e.target.value)}
                            aria-describedby={errors.submission_deadline ? 'submission_deadline-erro' : undefined}
                        />
                        <InputError id="submission_deadline-erro" message={errors.submission_deadline} />
                    </div>

                    <fieldset className="grid gap-4 sm:grid-cols-2">
                        <legend className="mb-1 text-sm font-semibold">Votação popular</legend>
                        <div className="grid gap-2">
                            <Label htmlFor="voting_opens_at">Abre em</Label>
                            <Input
                                id="voting_opens_at"
                                type="datetime-local"
                                value={data.voting_opens_at}
                                onChange={(e) => setData('voting_opens_at', e.target.value)}
                                aria-describedby={errors.voting_opens_at ? 'voting_opens_at-erro' : undefined}
                            />
                            <InputError id="voting_opens_at-erro" message={errors.voting_opens_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="voting_closes_at">Fecha em</Label>
                            <Input
                                id="voting_closes_at"
                                type="datetime-local"
                                value={data.voting_closes_at}
                                onChange={(e) => setData('voting_closes_at', e.target.value)}
                                aria-describedby={errors.voting_closes_at ? 'voting_closes_at-erro' : undefined}
                            />
                            <InputError id="voting_closes_at-erro" message={errors.voting_closes_at} />
                        </div>
                    </fieldset>

                    <fieldset className="grid gap-4 sm:grid-cols-2">
                        <legend className="mb-1 text-sm font-semibold">Tamanho da equipe</legend>
                        <div className="grid gap-2">
                            <Label htmlFor="min_team_size">Mínimo</Label>
                            <Input
                                id="min_team_size"
                                type="number"
                                min={1}
                                value={data.min_team_size}
                                onChange={(e) => setData('min_team_size', e.target.value)}
                                aria-describedby={errors.min_team_size ? 'min_team_size-erro' : undefined}
                            />
                            <InputError id="min_team_size-erro" message={errors.min_team_size} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="max_team_size">Máximo</Label>
                            <Input
                                id="max_team_size"
                                type="number"
                                min={1}
                                value={data.max_team_size}
                                onChange={(e) => setData('max_team_size', e.target.value)}
                                aria-describedby={errors.max_team_size ? 'max_team_size-erro' : undefined}
                            />
                            <InputError id="max_team_size-erro" message={errors.max_team_size} />
                        </div>
                    </fieldset>

                    <div className="grid gap-2">
                        <Label htmlFor="regulamento">Regulamento em PDF (opcional)</Label>
                        <Input
                            id="regulamento"
                            type="file"
                            accept=".pdf"
                            onChange={(e) => setData('regulamento', e.target.files?.[0] ?? null)}
                            aria-describedby={errors.regulamento ? 'regulamento-erro' : undefined}
                        />
                        <InputError id="regulamento-erro" message={errors.regulamento} />
                        <p className="text-muted-foreground text-xs">
                            Sem o PDF em mãos agora? Sem problema — dá pra anexar depois em Editar evento.
                        </p>
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row-reverse sm:justify-start">
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            {processing ? 'Criando…' : 'Criar evento'}
                        </Button>
                    </div>
                </form>
            </motion.div>
        </AppLayout>
    );
}
