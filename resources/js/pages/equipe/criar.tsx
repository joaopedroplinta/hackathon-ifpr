import { Head, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle, UsersRound } from 'lucide-react';
import { FormEventHandler } from 'react';

import ResumoErro from '@/components/hackathon/resumo-erro';
import SecaoFormulario from '@/components/hackathon/secao-formulario';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';

type Trilha = { id: number; name: string; description: string | null };

type EquipeForm = {
    name: string;
    description: string;
    track_id: string;
};

interface Props {
    trilhas: Trilha[];
    limites: { minimo: number; maximo: number };
}

export default function CriarEquipe({ trilhas, limites }: Props) {
    const { data, setData, post, processing, errors } = useForm<EquipeForm>({
        name: '',
        description: '',
        track_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('teams.store'));
    };

    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Equipe', href: route('teams.show') },
                { title: 'Criar', href: route('teams.create') },
            ]}
        >
            <Head title="Criar equipe" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-3xl p-4 sm:p-8">
                <header className="mb-8 flex items-start gap-4">
                    <span className="bg-primary/10 flex size-12 shrink-0 items-center justify-center rounded-2xl">
                        <UsersRound className="text-primary size-6" aria-hidden="true" />
                    </span>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Dê forma à sua equipe</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Você vira o líder. Depois é só passar o código de convite para o resto do time — de {limites.minimo} a {limites.maximo}{' '}
                            pessoas.
                        </p>
                    </div>
                </header>

                <form onSubmit={submit} className="space-y-6" noValidate>
                    <ResumoErro erros={errors} />
                    <SecaoFormulario
                        titulo="Identidade da equipe"
                        instrucao="Escolha um nome fácil de reconhecer e conte, em poucas palavras, o que vocês querem construir."
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nome da equipe</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoFocus
                                maxLength={60}
                                placeholder="Ex.: Os Devs"
                                aria-describedby={errors.name ? 'name-erro' : undefined}
                            />
                            <InputError id="name-erro" message={errors.name} />
                        </div>

                        {trilhas.length > 0 && (
                            <div className="grid gap-2">
                                <Label htmlFor="track_id">Trilha</Label>
                                <Select value={data.track_id} onValueChange={(value) => setData('track_id', value)}>
                                    <SelectTrigger id="track_id">
                                        <SelectValue placeholder="Selecione (pode decidir depois)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {trilhas.map((trilha) => (
                                            <SelectItem key={trilha.id} value={String(trilha.id)}>
                                                {trilha.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.track_id} />
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="description">Descrição</Label>
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={3}
                                maxLength={1000}
                                placeholder="Em uma frase, o que vocês pretendem construir."
                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                aria-describedby={errors.description ? 'description-erro' : undefined}
                            />
                            <InputError id="description-erro" message={errors.description} />
                        </div>

                        <div className="border-border border-t pt-5">
                            <Button type="submit" disabled={processing} className="h-11 w-full sm:w-auto">
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                {processing ? 'Criando…' : 'Criar equipe'}
                            </Button>
                        </div>
                    </SecaoFormulario>
                </form>
            </motion.div>
        </AppLayout>
    );
}
