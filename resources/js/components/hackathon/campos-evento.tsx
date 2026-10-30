import { type ReactNode } from 'react';

import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type NovoEventoForm } from '@/types/evento-admin';

/** Os campos que criar e editar evento têm em comum (tudo menos regulamento, fase e certificado). */
export type CamposComuns = Pick<
    NovoEventoForm,
    | 'name'
    | 'description'
    | 'registration_opens_at'
    | 'registration_closes_at'
    | 'starts_at'
    | 'ends_at'
    | 'submission_deadline'
    | 'voting_opens_at'
    | 'voting_closes_at'
    | 'min_team_size'
    | 'max_team_size'
    | 'collect_shirt_size'
    | 'collect_dietary_notes'
>;

type Props = {
    data: CamposComuns;
    errors: Partial<Record<keyof CamposComuns, string>>;
    setData: (campo: keyof CamposComuns, valor: string | boolean) => void;
    /** Dica de nome e tema só aparece na criação, onde o campo começa vazio. */
    exemplos?: { nome: string; tema: string };
    /** Entra entre o tema e as inscrições (a edição põe a fase do evento aqui). */
    aposTema?: ReactNode;
};

const areaTexto =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none';
const grupo = 'border-border/70 bg-muted/20 grid gap-4 rounded-2xl border p-4';

/** Rótulo + input + mensagem de erro ligada por aria-describedby. */
function Campo({
    id,
    rotulo,
    valor,
    erro,
    onChange,
    type = 'text',
    ...extra
}: {
    id: keyof CamposComuns;
    rotulo: string;
    valor: string;
    erro?: string;
    onChange: (valor: string) => void;
    type?: string;
    maxLength?: number;
    min?: number;
    placeholder?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{rotulo}</Label>
            <Input
                id={id}
                type={type}
                value={valor}
                onChange={(e) => onChange(e.target.value)}
                aria-describedby={erro ? `${id}-erro` : undefined}
                {...extra}
            />
            <InputError id={`${id}-erro`} message={erro} />
        </div>
    );
}

export default function CamposEvento({ data, errors, setData, exemplos, aposTema }: Props) {
    const campo = (id: keyof CamposComuns, rotulo: string, extra: { type?: string; min?: number } = {}) => (
        <Campo id={id} rotulo={rotulo} valor={String(data[id])} erro={errors[id]} onChange={(valor) => setData(id, valor)} {...extra} />
    );

    return (
        <>
            <Campo
                id="name"
                rotulo="Nome do evento"
                valor={data.name}
                erro={errors.name}
                onChange={(valor) => setData('name', valor)}
                maxLength={120}
                placeholder={exemplos?.nome}
            />

            <div className="grid gap-2">
                <Label htmlFor="description">Tema / desafio</Label>
                <textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    rows={4}
                    placeholder={exemplos?.tema}
                    className={areaTexto}
                    aria-describedby={errors.description ? 'description-erro' : undefined}
                />
                <InputError id="description-erro" message={errors.description} />
            </div>

            {aposTema}

            <fieldset className={`${grupo} sm:grid-cols-2`}>
                <legend className="bg-card px-2 text-sm font-medium">Inscrições</legend>
                {campo('registration_opens_at', 'Abre em', { type: 'datetime-local' })}
                {campo('registration_closes_at', 'Fecha em', { type: 'datetime-local' })}
            </fieldset>

            <fieldset className={`${grupo} gap-3`}>
                <legend className="bg-card px-2 text-sm font-medium">Dados solicitados na inscrição</legend>
                <p className="text-muted-foreground text-sm">Ative somente o que este evento realmente vai oferecer.</p>
                <label className="flex min-h-11 items-center gap-3 text-sm">
                    <input
                        type="checkbox"
                        checked={data.collect_shirt_size}
                        onChange={(e) => setData('collect_shirt_size', e.target.checked)}
                        className="accent-primary size-4"
                    />
                    Solicitar tamanho de camiseta
                </label>
                <label className="flex min-h-11 items-center gap-3 text-sm">
                    <input
                        type="checkbox"
                        checked={data.collect_dietary_notes}
                        onChange={(e) => setData('collect_dietary_notes', e.target.checked)}
                        className="accent-primary size-4"
                    />
                    Solicitar restrições alimentares
                </label>
            </fieldset>

            <fieldset className={`${grupo} sm:grid-cols-2`}>
                <legend className="bg-card px-2 text-sm font-medium">Evento</legend>
                {campo('starts_at', 'Início', { type: 'datetime-local' })}
                {campo('ends_at', 'Fim', { type: 'datetime-local' })}
            </fieldset>

            {campo('submission_deadline', 'Prazo de submissão', { type: 'datetime-local' })}

            <fieldset className={`${grupo} sm:grid-cols-2`}>
                <legend className="bg-card px-2 text-sm font-medium">Votação popular</legend>
                {campo('voting_opens_at', 'Abre em', { type: 'datetime-local' })}
                {campo('voting_closes_at', 'Fecha em', { type: 'datetime-local' })}
            </fieldset>

            <fieldset className={`${grupo} sm:grid-cols-2`}>
                <legend className="bg-card px-2 text-sm font-medium">Tamanho da equipe</legend>
                {campo('min_team_size', 'Mínimo', { type: 'number', min: 1 })}
                {campo('max_team_size', 'Máximo', { type: 'number', min: 1 })}
            </fieldset>
        </>
    );
}
