import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

import { SharedData } from '@/types';

/**
 * Dispara o toast de sucesso/erro depois de um POST -- só um lugar decide
 * isto (montado uma vez no layout autenticado e uma vez no cabeçalho
 * público), em vez de cada página lembrar de ler `flash` sozinha (a
 * maioria não lembrava: salvar em /admin/evento não mostrava nada).
 * `<Toaster/>` (app-toaster.tsx) é quem desenha -- aqui só dispara.
 */
export default function FlashMessages() {
    const { flash } = usePage<SharedData>().props;

    useEffect(() => {
        if (flash?.sucesso) {
            toast.success(flash.sucesso);
        }

        if (flash?.erro) {
            toast.error(flash.erro);
        }
    }, [flash?.sucesso, flash?.erro]);

    return null;
}
