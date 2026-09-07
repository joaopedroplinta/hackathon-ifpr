import { Link } from '@inertiajs/react';
import { type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

type Acao = { href: string; texto: string };

type EstadoVazioProps = {
    icon?: LucideIcon;
    titulo: string;
    descricao?: ReactNode;
    acao?: Acao;
    className?: string;
};

/**
 * Estado vazio com causa e próxima ação -- nunca uma lista/tabela em branco.
 * Usar título e descrição diferentes quando a causa é filtro (zero resultado)
 * versus ausência real de dado (zero registro): .claude/rules/frontend.md.
 */
export default function EstadoVazio({ icon: Icon, titulo, descricao, acao, className }: EstadoVazioProps) {
    return (
        <div className={cn('border-border bg-card flex flex-col items-center gap-3 rounded-2xl border p-10 text-center', className)}>
            {Icon && (
                <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                    <Icon className="text-muted-foreground size-5" aria-hidden="true" />
                </span>
            )}
            <p className="font-semibold">{titulo}</p>
            {descricao && <p className="text-muted-foreground max-w-sm text-sm leading-relaxed">{descricao}</p>}
            {acao && (
                <Button asChild className="mt-2 h-11">
                    <Link href={acao.href}>{acao.texto}</Link>
                </Button>
            )}
        </div>
    );
}
