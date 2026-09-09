import { useId, type ReactNode } from 'react';

import { cn } from '@/lib/utils';

type SecaoFormularioProps = {
    titulo: string;
    instrucao?: ReactNode;
    children: ReactNode;
    className?: string;
};

/** Bloco de formulário com título e instrução -- agrupa campos por tarefa, não por tipo de input. */
export default function SecaoFormulario({ titulo, instrucao, children, className }: SecaoFormularioProps) {
    const tituloId = useId();

    return (
        <section
            aria-labelledby={tituloId}
            className={cn(
                'border-border/90 bg-card rounded-2xl border p-5 shadow-[0_1px_0_color-mix(in_oklab,var(--border)_55%,transparent)] sm:p-8',
                className,
            )}
        >
            <div className="mb-6 flex flex-col gap-1">
                <h2 id={tituloId} className="text-xl font-semibold tracking-[-0.025em]">
                    {titulo}
                </h2>
                {instrucao && <p className="text-muted-foreground text-sm leading-relaxed">{instrucao}</p>}
            </div>
            <div className="flex flex-col gap-5">{children}</div>
        </section>
    );
}
