import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

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

export default function CriarInscricao({ tamanhos }: { tamanhos: Tamanho[] }) {
    const { evento } = usePage<SharedData>().props;

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

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Início', href: route('dashboard') },
                { title: 'Inscrição', href: route('registration.create') },
            ]}
        >
            <Head title="Inscrição" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <header className="mb-8">
                    <h1 className="text-2xl font-semibold">Inscrição no evento</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        {evento?.nome}. Todos os campos abaixo são opcionais — servem para a organização se preparar melhor.
                    </p>
                </header>

                <form onSubmit={submit} className="grid gap-6">
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
                        <InputError message={errors.shirt_size} />
                    </div>

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
                        <InputError id="dietary-erro" message={errors.dietary_notes} />
                    </div>

                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        {processing ? 'Confirmando…' : 'Confirmar inscrição'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
