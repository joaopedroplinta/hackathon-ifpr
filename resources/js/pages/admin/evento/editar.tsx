import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import PainelRegulamento from '@/components/hackathon/painel-regulamento';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { EventoExistente, EventoForm, OpcaoStatusEvento, RegulamentoEvento } from '@/types/evento-admin';

interface Props {
    evento: EventoExistente;
    status_opcoes: OpcaoStatusEvento[];
    regulamento: RegulamentoEvento;
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

/**
 * Brasil não tem mais horário de verão desde 2019 -- América/São_Paulo é
 * -03:00 o ano inteiro. Mesmo helper de admin/agenda/formulario.tsx, mas
 * seguro pra campo vazio: nem toda janela do evento é obrigatória.
 */
function paraIsoDeSaoPaulo(valorDatetimeLocal: string): string | null {
    if (!valorDatetimeLocal) {
        return null;
    }

    return new Date(`${valorDatetimeLocal}:00-03:00`).toISOString();
}

function deIsoParaDatetimeLocal(iso: string | null): string {
    if (!iso) {
        return '';
    }

    return new Date(iso).toLocaleString('sv-SE', { timeZone: 'America/Sao_Paulo' }).slice(0, 16).replace(' ', 'T');
}

export default function EditarEvento({ evento, status_opcoes, regulamento }: Props) {
    const { data, setData, transform, patch, processing, errors } = useForm<EventoForm>({
        name: evento.name,
        description: evento.description ?? '',
        status: evento.status,
        registration_opens_at: deIsoParaDatetimeLocal(evento.registration_opens_at),
        registration_closes_at: deIsoParaDatetimeLocal(evento.registration_closes_at),
        starts_at: deIsoParaDatetimeLocal(evento.starts_at),
        ends_at: deIsoParaDatetimeLocal(evento.ends_at),
        submission_deadline: deIsoParaDatetimeLocal(evento.submission_deadline),
        voting_opens_at: deIsoParaDatetimeLocal(evento.voting_opens_at),
        voting_closes_at: deIsoParaDatetimeLocal(evento.voting_closes_at),
        min_team_size: String(evento.min_team_size),
        max_team_size: String(evento.max_team_size),
        certificate_signer_name: evento.certificate_signer_name ?? '',
        certificate_signer_role: evento.certificate_signer_role ?? '',
    });

    // transform() só muda o que vai pro servidor -- o campo continua
    // mostrando o horário local pro organizador, sem fuso no meio.
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
        patch(route('admin.evento.update'), { preserveScroll: true });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Evento', href: route('admin.evento.edit') }]}>
            <Head title="Editar evento" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <h1 className="mb-1 text-2xl font-bold tracking-tight">Editar evento</h1>
                <p className="text-muted-foreground mb-6 text-sm">
                    Tema aqui é o desafio que as equipes resolvem, não visual. Aparece na landing pública.
                </p>

                <form onSubmit={enviar} className="border-border bg-card grid gap-6 rounded-xl border p-6">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nome do evento</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
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

                    <div className="grid gap-2">
                        <Label htmlFor="status">Fase</Label>
                        <select
                            id="status"
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className={campo}
                            aria-describedby={errors.status ? 'status-erro' : undefined}
                        >
                            {status_opcoes.map((opcao) => (
                                <option key={opcao.value} value={opcao.value}>
                                    {opcao.label}
                                </option>
                            ))}
                        </select>
                        <InputError id="status-erro" message={errors.status} />
                        <p className="text-muted-foreground text-xs">Rascunho não aparece em nenhuma página pública.</p>
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

                    <fieldset className="grid gap-4 sm:grid-cols-2">
                        <legend className="mb-1 text-sm font-semibold">Assinatura do certificado</legend>
                        <div className="grid gap-2">
                            <Label htmlFor="certificate_signer_name">Nome de quem assina</Label>
                            <Input
                                id="certificate_signer_name"
                                value={data.certificate_signer_name}
                                onChange={(e) => setData('certificate_signer_name', e.target.value)}
                                placeholder="Ex.: Maria Souza"
                                maxLength={120}
                                aria-describedby={errors.certificate_signer_name ? 'certificate_signer_name-erro' : undefined}
                            />
                            <InputError id="certificate_signer_name-erro" message={errors.certificate_signer_name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="certificate_signer_role">Cargo</Label>
                            <Input
                                id="certificate_signer_role"
                                value={data.certificate_signer_role}
                                onChange={(e) => setData('certificate_signer_role', e.target.value)}
                                placeholder="Ex.: Coordenadora do Hackathon"
                                maxLength={120}
                                aria-describedby={errors.certificate_signer_role ? 'certificate_signer_role-erro' : undefined}
                            />
                            <InputError id="certificate_signer_role-erro" message={errors.certificate_signer_role} />
                        </div>
                        <p className="text-muted-foreground text-xs sm:col-span-2">
                            Aparece no rodapé do certificado em PDF. Deixe em branco pra não mostrar nenhuma assinatura.
                        </p>
                    </fieldset>

                    <div className="flex flex-col gap-3 sm:flex-row-reverse sm:justify-start">
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            {processing ? 'Salvando…' : 'Salvar alterações'}
                        </Button>
                    </div>
                </form>

                <div className="mt-6">
                    <PainelRegulamento regulamento={regulamento} />
                </div>
            </motion.div>
        </AppLayout>
    );
}
