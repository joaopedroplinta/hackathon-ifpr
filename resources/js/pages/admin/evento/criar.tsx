import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import CamposEvento from '@/components/hackathon/campos-evento';
import ResumoErro from '@/components/hackathon/resumo-erro';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { NovoEventoForm } from '@/types/evento-admin';

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
        collect_shirt_size: false,
        collect_dietary_notes: false,
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
        post(route('painel.evento.store'), { preserveScroll: true, forceFormData: true });
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Evento', href: route('painel.evento.create') }]}>
            <Head title="Criar evento" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-3xl p-4 sm:p-6 lg:p-8">
                <header className="border-border/70 mb-8 border-b pb-6">
                    <p className="text-muted-foreground mb-2 text-sm">Configuração da edição</p>
                    <h1 className="text-3xl font-medium tracking-[-0.03em]">Criar evento</h1>
                    <p className="text-muted-foreground mt-2 max-w-2xl text-sm leading-relaxed">
                        Ainda não existe nenhuma edição do hackathon cadastrada. Preencha o essencial agora — as demais telas do painel (agenda,
                        jurados, avaliação) só ficam disponíveis depois que o primeiro evento existir. Você pode ajustar tudo de novo em Editar
                        evento.
                    </p>
                </header>

                <form onSubmit={enviar} className="border-border/70 bg-card/80 grid gap-6 rounded-3xl border p-5 shadow-sm sm:p-8" noValidate>
                    <ResumoErro erros={errors} />
                    <CamposEvento
                        data={data}
                        errors={errors}
                        setData={(campo, valor) => setData(campo, valor as never)}
                        exemplos={{ nome: 'Ex.: Hackathon IFPR Pinhais 2026', tema: 'Ex.: soluções inovadoras para mobilidade urbana' }}
                    />

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
