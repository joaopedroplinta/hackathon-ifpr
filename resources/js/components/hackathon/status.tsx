import { AlertTriangle, CheckCircle2, Circle, Info, XCircle, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

import { cn } from '@/lib/utils';

const tons = {
    sucesso: { icon: CheckCircle2, classe: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400' },
    atencao: { icon: AlertTriangle, classe: 'bg-amber-500/15 text-amber-700 dark:text-amber-400' },
    erro: { icon: XCircle, classe: 'bg-red-500/15 text-red-700 dark:text-red-400' },
    info: { icon: Info, classe: 'bg-primary/10 text-primary' },
    neutro: { icon: Circle, classe: 'bg-muted text-muted-foreground' },
} as const;

type Tom = keyof typeof tons;

type StatusProps = {
    tom: Tom;
    children: ReactNode;
    icon?: LucideIcon;
    className?: string;
};

/**
 * Selo de situação com ícone + texto -- cor nunca é a única informação
 * (.claude/rules/frontend.md). Usar nos lugares que hoje só trocam a cor
 * de um <span> (ex.: corDoStatus em admin/submissoes/index.tsx).
 */
export default function Status({ tom, children, icon, className }: StatusProps) {
    const config = tons[tom];
    const Icon = icon ?? config.icon;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full border border-current/10 px-2.5 py-1 text-xs font-medium',
                config.classe,
                className,
            )}
        >
            <Icon className="size-3.5 shrink-0" aria-hidden="true" />
            {children}
        </span>
    );
}
