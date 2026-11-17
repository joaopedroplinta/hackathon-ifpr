import { router, usePage } from '@inertiajs/react';
import { type Driver, type DriveStep } from 'driver.js';
import { useCallback, useEffect, useRef } from 'react';

import { tours, type TourDefinition } from '@/lib/tours';
import { type SharedData } from '@/types';

/** Espera a página montar (e o fade-in do ContainerPagina) para os alvos existirem. */
const ATRASO_AUTO_MS = 700;

const seletor = (alvo: string) => (alvo === 'titulo' ? '#conteudo h1' : `[data-tour="${alvo}"]`);

/** Alvo precisa existir e estar visível: menu recolhido no celular some do DOM ou fica oculto. */
function visivel(el: Element | null): el is HTMLElement {
    return el instanceof HTMLElement && el.getClientRects().length > 0;
}

function montarPassos(tour: TourDefinition): DriveStep[] {
    return tour.steps.flatMap((passo) => {
        const el = document.querySelector(seletor(passo.target));
        return visivel(el) ? [{ element: el, popover: { title: passo.title, description: passo.description } }] : [];
    });
}

/**
 * Tutorial guiado da página atual. Abre sozinho na primeira visita (só para
 * quem está logado -- o "já vi" mora no servidor) e pode ser revisto por
 * `start()`. Marca como visto ao concluir, pular ou fechar; rever não chama a
 * API de novo.
 */
export function useTour() {
    const { component, props } = usePage<SharedData>();
    const tour: TourDefinition | undefined = tours[component];
    const user = props.auth?.user;
    const jaVisto = !!tour && !!user?.tours_seen?.includes(tour.id);
    const ativo = useRef<Driver | null>(null);
    const autoIniciado = useRef<string | null>(null);
    const timerAuto = useRef<number | undefined>(undefined);
    const montado = useRef(true);
    // Fechar por troca de página não é "ver o tutorial": não grava como visto.
    const descartando = useRef(false);

    const iniciar = useCallback(async () => {
        if (!tour) return;
        // Início manual (ou automático) cancela a abertura automática pendente.
        window.clearTimeout(timerAuto.current);
        autoIniciado.current = tour.id;

        const [{ driver }] = await Promise.all([import('driver.js'), import('driver.js/dist/driver.css')]);
        // Saiu da página enquanto o chunk carregava: os alvos são da tela antiga.
        if (!montado.current) return;

        // Montados só depois do import: o DOM pode ter mudado durante a espera.
        const steps = montarPassos(tour);
        if (steps.length === 0) return;

        descartando.current = true;
        ativo.current?.destroy();
        descartando.current = false;

        const reduzMovimento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const instancia = driver({
            steps,
            animate: !reduzMovimento,
            smoothScroll: !reduzMovimento,
            showProgress: true,
            progressText: 'Passo {{current}} de {{total}}',
            nextBtnText: 'Próximo',
            prevBtnText: 'Anterior',
            doneBtnText: 'Concluir',
            popoverClass: 'tour-popover',
            overlayOpacity: 0.65,
            onPopoverRender: (popover, { driver: d }) => {
                popover.closeButton.setAttribute('aria-label', 'Fechar tutorial');
                const pular = document.createElement('button');
                pular.type = 'button';
                pular.textContent = 'Pular';
                pular.className = 'tour-skip-btn';
                pular.addEventListener('click', () => d.destroy());
                popover.footerButtons.prepend(pular);
            },
            onDestroyed: () => {
                ativo.current = null;
                if (!descartando.current && user && !jaVisto) {
                    router.post(route('tours.seen', tour.id), {}, { preserveScroll: true, preserveState: true, only: ['auth'] });
                }
            },
        });
        ativo.current = instancia;
        instancia.drive();
    }, [tour, user, jaVisto]);

    useEffect(() => {
        // `manual`: telas de uso crítico no evento só abrem o tutorial pelo botão.
        if (!tour || tour.manual || !user || jaVisto || autoIniciado.current === tour.id) return;
        timerAuto.current = window.setTimeout(() => void iniciar(), ATRASO_AUTO_MS);
        return () => window.clearTimeout(timerAuto.current);
    }, [tour, user, jaVisto, iniciar]);

    useEffect(() => {
        montado.current = true;
        return () => {
            montado.current = false;
            const d = ativo.current;
            ativo.current = null;
            descartando.current = true;
            d?.destroy();
        };
    }, []);

    return { temTour: !!tour, iniciar };
}
