import { Toaster } from 'sonner';

import { useAppearance } from '@/hooks/use-appearance';

/**
 * Um único <Toaster/> pro app inteiro -- ver flash-messages.tsx, que dispara
 * o toast. `theme` segue o mesmo estado do nosso próprio alternador
 * (claro/escuro/sistema), não o tema do sistema operacional sozinho.
 */
export default function AppToaster() {
    const { appearance } = useAppearance();

    return (
        <Toaster
            theme={appearance}
            position="top-right"
            richColors
            closeButton
            toastOptions={{
                classNames: {
                    toast: 'font-sans',
                },
            }}
        />
    );
}
