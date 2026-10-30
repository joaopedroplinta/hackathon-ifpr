import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import CamposEvento from '@/components/hackathon/campos-evento';
import PainelLogoCertificado from '@/components/hackathon/painel-logo-certificado';
import PainelRegulamento from '@/components/hackathon/painel-regulamento';
import ResumoErro from '@/components/hackathon/resumo-erro';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { CertificadoLogoEvento, EventoExistente, EventoForm, OpcaoStatusEvento, RegulamentoEvento } from '@/types/evento-admin';

interface Props {
    evento: EventoExistente;
    status_opcoes: OpcaoStatusEvento[];
    regulamento: RegulamentoEvento;
    certificado_logo: CertificadoLogoEvento;
}

const campo =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';

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

export default function EditarEvento({ evento, status_opcoes, regulamento, certificado_logo }: Props) {
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
        collect_shirt_size: evento.collect_shirt_size,
        collect_dietary_notes: evento.collect_dietary_notes,
        certificate_signer_name: evento.certificate_signer_name ?? '',
        certificate_signer_role: evento.certificate_signer_role ?? '',
        certificate_accent_color: evento.certificate_accent_color ?? '',
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
        patch(route('painel.evento.update'), { preserveScroll: true });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Evento', href: route('painel.evento.edit') }]}>
            <Head title="Editar evento" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-3xl p-4 sm:p-6 lg:p-8">
                <header className="border-border/70 mb-8 border-b pb-6">
                    <p className="text-muted-foreground mb-2 text-sm">Configuração da edição</p>
                    <h1 className="text-3xl font-medium tracking-[-0.03em]">Editar evento</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Tema aqui é o desafio que as equipes resolvem, não visual. Aparece na landing pública.
                    </p>
                </header>

                <form onSubmit={enviar} className="border-border/70 bg-card/80 grid gap-6 rounded-3xl border p-5 shadow-sm sm:p-8" noValidate>
                    <ResumoErro erros={errors} />
                    <CamposEvento
                        data={data}
                        errors={errors}
                        setData={(campo, valor) => setData(campo, valor as never)}
                        aposTema={
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
                        }
                    />

                    <fieldset className="border-border/70 bg-muted/20 grid gap-4 rounded-2xl border p-4 sm:grid-cols-2">
                        <legend className="bg-card px-2 text-sm font-medium">Assinatura do certificado</legend>
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

                    <fieldset className="border-border/70 bg-muted/20 grid gap-4 rounded-2xl border p-4">
                        <legend className="bg-card px-2 text-sm font-medium">Visual do certificado</legend>
                        <div className="grid max-w-[200px] gap-2">
                            <Label htmlFor="certificate_accent_color">Cor de destaque</Label>
                            <div className="flex items-center gap-2">
                                <input
                                    id="certificate_accent_color_picker"
                                    type="color"
                                    value={data.certificate_accent_color || '#357724'}
                                    onChange={(e) => setData('certificate_accent_color', e.target.value)}
                                    className="border-input h-10 w-12 shrink-0 rounded-md border"
                                    aria-label="Escolher cor de destaque"
                                />
                                <Input
                                    id="certificate_accent_color"
                                    value={data.certificate_accent_color}
                                    onChange={(e) => setData('certificate_accent_color', e.target.value)}
                                    placeholder="#357724"
                                    maxLength={7}
                                    aria-describedby={errors.certificate_accent_color ? 'certificate_accent_color-erro' : undefined}
                                />
                            </div>
                            <InputError id="certificate_accent_color-erro" message={errors.certificate_accent_color} />
                            <p className="text-muted-foreground text-xs">Usada na borda e nos rótulos do PDF. Deixe em branco pra usar o padrão.</p>
                        </div>
                    </fieldset>

                    <div className="flex flex-col gap-3 sm:flex-row-reverse sm:justify-start">
                        <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            {processing ? 'Salvando…' : 'Salvar alterações'}
                        </Button>
                    </div>
                </form>

                <div className="mt-6 grid gap-6">
                    <PainelRegulamento regulamento={regulamento} />
                    <PainelLogoCertificado certificadoLogo={certificado_logo} />
                </div>
            </motion.div>
        </AppLayout>
    );
}
