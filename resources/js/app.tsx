import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import AppToaster from './components/app-toaster';
import AvisoCookies from './components/hackathon/aviso-cookies';
import { initializeTheme } from './hooks/use-appearance';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <AppToaster />
                <AvisoCookies />
            </>,
        );
    },
    // Verde-ifpr, não o cinza padrão do starter kit -- PLANO.md §11.
    progress: {
        color: '#357724',
    },
}).catch(console.error);

// This will set light / dark mode on load...
initializeTheme();

// Transição suave entre páginas: o conteúdo esmaece durante a navegação
// em vez de trocar de forma abrupta. Prefetch (hover nos links do menu) não
// é navegação: sem este filtro a tela piscava a cada passada de mouse.
router.on('start', (event) => {
    if (event.detail.visit.prefetch) return;
    document.getElementById('app')?.classList.add('opacity-60');
});
router.on('finish', () => document.getElementById('app')?.classList.remove('opacity-60'));
