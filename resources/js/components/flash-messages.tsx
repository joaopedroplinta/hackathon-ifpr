import { usePage } from '@inertiajs/react';
import { CheckCircle2, CircleAlert } from 'lucide-react';

import { SharedData } from '@/types';

/**
 * Mensagem de sucesso/erro depois de um POST -- só um lugar decide isto,
 * renderizado uma vez no layout autenticado e uma vez no cabeçalho público,
 * em vez de cada página lembrar de ler `flash` sozinha (a maioria não
 * lembrava, por isso salvar em /admin/evento não mostrava nada).
 */
export default function FlashMessages() {
    const { flash } = usePage<SharedData>().props;

    if (!flash?.sucesso && !flash?.erro) {
        return null;
    }

    const sucesso = flash.sucesso;

    return (
        <div
            role={sucesso ? 'status' : 'alert'}
            aria-live={sucesso ? 'polite' : 'assertive'}
            className={`animate-in fade-in slide-in-from-top-2 mx-4 mt-4 flex items-start gap-2 rounded-xl border p-4 text-sm sm:mx-6 ${
                sucesso
                    ? 'border-green-600/30 bg-green-600/10 text-green-800 dark:text-green-300'
                    : 'border-red-600/30 bg-red-600/10 text-red-800 dark:text-red-300'
            }`}
        >
            {sucesso ? (
                <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            ) : (
                <CircleAlert className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            )}
            <span>{sucesso ?? flash.erro}</span>
        </div>
    );
}
