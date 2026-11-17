import { CircleHelp } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { useTour } from '@/hooks/use-tour';

/** "Ver o tutorial desta tela". Só aparece quando a página atual tem tutorial. */
export default function BotaoTutorial() {
    const { temTour, iniciar } = useTour();

    if (!temTour) return null;

    return (
        <Button
            type="button"
            variant="ghost"
            size="icon"
            className="hover:bg-muted size-10 rounded-xl"
            aria-label="Ver o tutorial desta tela"
            title="Ver o tutorial desta tela"
            onClick={() => void iniciar()}
        >
            <CircleHelp className="size-5" aria-hidden="true" />
        </Button>
    );
}
