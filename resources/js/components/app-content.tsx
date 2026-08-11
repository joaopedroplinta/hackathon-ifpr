import { SidebarInset } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'div'> {
    variant?: 'header' | 'sidebar';
}

export function AppContent({ variant = 'header', className, children, ...props }: AppContentProps) {
    if (variant === 'sidebar') {
        // min-w-0: o <main> é flex item e nasce com min-width:auto, então cresce
        // até caber o conteúdo mais largo. Uma tabela com min-w dentro de um
        // overflow-x-auto faria a PÁGINA inteira rolar na horizontal no celular,
        // em vez da tabela -- .claude/rules/frontend.md.
        return (
            <SidebarInset className={cn('min-w-0', className)} {...props}>
                {children}
            </SidebarInset>
        );
    }

    return (
        <main className={cn('mx-auto flex h-full w-full max-w-7xl min-w-0 flex-1 flex-col gap-4 rounded-xl', className)} {...props}>
            {children}
        </main>
    );
}
