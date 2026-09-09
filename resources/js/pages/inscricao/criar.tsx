import { Head, useForm, usePage } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { CheckCircle2, LoaderCircle, LockKeyhole, Sparkles } from 'lucide-react';
import { FormEventHandler } from 'react';

import ResumoErro from '@/components/hackathon/resumo-erro';
import SecaoFormulario from '@/components/hackathon/secao-formulario';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { SharedData } from '@/types';

type Tamanho = { value: string; label: string };

type InscricaoForm = {
    shirt_size: string;
    dietary_notes: string;
    phone: string;
    course: string;
};

export default function CriarInscricao({
    tamanhos,
    coletar_camisa: coletarCamisa,
    coletar_restricoes_alimentares: coletarRestricoesAlimentares,
}: {
    tamanhos: Tamanho[];
    coletar_camisa: boolean;
    coletar_restricoes_alimentares: boolean;
}) {
    const { evento } = usePage<SharedData>().props;
    const reduzMovimento = useReducedMotion();

    const { data, setData, post, processing, errors } = useForm<InscricaoForm>({
        shirt_size: '',
        dietary_notes: '',
        phone: '',
        course: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('registration.store'));
    };

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Início', href: route('dashboard') },
                { title: 'Inscrição', href: route('registration.create') },
            ]}
        >
            <Head title="Inscrição" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-5xl p-4 sm:p-8">
                <header className="mb-8">
                    <h1 className="text-2xl font-bold tracking-tight">Inscrição no evento</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {evento?.nome}. Todos os campos abaixo são opcionais — servem para a organização se preparar melhor.
                    </p>
                </header>

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <form onSubmit={submit} className="space-y-6" noValidate>
                        <ResumoErro erros={errors} />
                        <SecaoFormulario
                            titulo="Informações para o evento"
                            instrucao="Todos os campos são opcionais e ajudam a organização a preparar melhor sua participação."
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="course">Curso</Label>
                                <Input
                                    id="course"
                                    value={data.course}
                                    onChange={(e) => setData('course', e.target.value)}
                                    placeholder="Ex.: Análise e Desenvolvimento de Sistemas"
                                    aria-describedby={errors.course ? 'course-erro' : undefined}
                                />
                                <InputError id="course-erro" message={errors.course} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telefone</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    placeholder="(41) 90000-0000"
                                    aria-describedby={errors.phone ? 'phone-erro' : undefined}
                                />
                                <InputError id="phone-erro" message={errors.phone} />
                                <p className="text-muted-foreground text-xs">Usado só para contato urgente durante o evento.</p>
                            </div>

                            {coletarCamisa && (
                                <div className="grid gap-2">
                                    <Label htmlFor="shirt_size">Tamanho da camiseta</Label>
                                    <Select value={data.shirt_size} onValueChange={(value) => setData('shirt_size', value)}>
                                        <SelectTrigger id="shirt_size">
                                            <SelectValue placeholder="Selecione" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {tamanhos.map((tamanho) => (
                                                <SelectItem key={tamanho.value} value={tamanho.value}>
                                                    {tamanho.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-muted-foreground text-xs">Informe apenas se quiser receber a camiseta do evento.</p>
                                    <InputError message={errors.shirt_size} />
                                </div>
                            )}

                            {coletarRestricoesAlimentares && (
                                <div className="grid gap-2">
                                    <Label htmlFor="dietary_notes">Restrições alimentares</Label>
                                    <textarea
                                        id="dietary_notes"
                                        value={data.dietary_notes}
                                        onChange={(e) => setData('dietary_notes', e.target.value)}
                                        rows={3}
                                        maxLength={500}
                                        placeholder="Vegetariano, alergia a amendoim, intolerância a lactose…"
                                        className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                        aria-describedby={errors.dietary_notes ? 'dietary-erro' : undefined}
                                    />
                                    <p className="text-muted-foreground text-xs">Use este campo para a alimentação oferecida durante o evento.</p>
                                    <InputError id="dietary-erro" message={errors.dietary_notes} />
                                </div>
                            )}

                            <div className="border-border border-t pt-5">
                                <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                    {processing ? 'Confirmando…' : 'Confirmar inscrição'}
                                </Button>
                            </div>
                        </SecaoFormulario>
                    </form>
                    <aside
                        className="border-border bg-card overflow-hidden rounded-2xl border lg:sticky lg:top-20"
                        aria-label="Como funciona a inscrição"
                    >
                        <div className="bg-primary/10 border-border border-b p-5">
                            <Sparkles className="text-primary size-5" aria-hidden="true" />
                            <h2 className="mt-3 font-semibold">Tudo pronto em poucos minutos</h2>
                            <p className="text-muted-foreground mt-1 text-sm">Você poderá formar sua equipe assim que confirmar.</p>
                        </div>
                        <ul className="space-y-4 p-5 text-sm">
                            <li className="flex gap-3">
                                <CheckCircle2 className="text-primary mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>Os campos desta etapa são opcionais.</span>
                            </li>
                            <li className="flex gap-3">
                                <LockKeyhole className="text-primary mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>Restrições alimentares recebem cuidado especial de privacidade.</span>
                            </li>
                            <li className="flex gap-3">
                                <CheckCircle2 className="text-primary mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                <span>Você pode revisar seus dados depois nas configurações.</span>
                            </li>
                        </ul>
                    </aside>
                </div>
            </motion.div>
        </AppLayout>
    );
}
