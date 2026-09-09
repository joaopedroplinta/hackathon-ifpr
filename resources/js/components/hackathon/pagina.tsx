import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

const larguras = {
    // Formulário: uma coluna, sem esticar rótulo/campo em telas largas.
    formulario: 'max-w-2xl',
    // Leitura: prosa institucional (privacidade, regulamento, rubrica).
    leitura: 'max-w-3xl',
    // Operação: listagem, painel com aside, maioria das telas internas.
    operacao: 'max-w-5xl',
    // Painel: dashboards com duas colunas largas (participante, avaliação).
    painel: 'max-w-6xl',
} as const;

type Largura = keyof typeof larguras;

type ContainerPaginaProps = {
    largura?: Largura;
    className?: string;
    children: ReactNode;
};

/**
 * Container padrão das páginas internas (dentro do AppLayout): largura por
 * função, não por preferência estética, e entrada suave respeitando
 * prefers-reduced-motion -- mesmo padrão usado em dashboard.tsx e admin/index.tsx.
 */
export function ContainerPagina({ largura = 'operacao', className, children }: ContainerPaginaProps) {
    const reduzMovimento = useReducedMotion();
    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <motion.div
            initial="oculto"
            animate="visivel"
            variants={fadeIn}
            className={cn('mx-auto flex w-full flex-col gap-8 px-4 py-6 sm:px-8 sm:py-9 lg:gap-10 lg:px-10', larguras[largura], className)}
        >
            {children}
        </motion.div>
    );
}

type CabecalhoPaginaProps = {
    eyebrow?: string;
    titulo: string;
    descricao?: ReactNode;
    acao?: ReactNode;
    className?: string;
};

/** Cabeçalho de página: eyebrow opcional, título, contexto e uma ação principal. */
export function CabecalhoPagina({ eyebrow, titulo, descricao, acao, className }: CabecalhoPaginaProps) {
    return (
        <header className={cn('border-border/80 flex flex-col justify-between gap-5 border-b pb-6 sm:flex-row sm:items-end', className)}>
            <div className="min-w-0">
                {eyebrow && <p className="text-primary mb-2 text-xs font-semibold tracking-[0.14em] uppercase">{eyebrow}</p>}
                <h1 className="text-3xl leading-tight font-semibold tracking-[-0.035em] text-balance sm:text-4xl">{titulo}</h1>
                {descricao && <div className="text-muted-foreground mt-3 max-w-2xl text-sm leading-relaxed sm:text-base">{descricao}</div>}
            </div>
            {acao && <div className="flex shrink-0 flex-wrap gap-2">{acao}</div>}
        </header>
    );
}
