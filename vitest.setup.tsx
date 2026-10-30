import { cleanup } from '@testing-library/react';
import { afterEach, beforeEach, vi } from 'vitest';

import { formErrors, setPage } from '@/test/inertia';

// Mesmo mock em todo teste: página recebe props por `setPage`, não por fetch.
vi.mock('@inertiajs/react', async () => (await import('@/test/inertia')).inertiaMock);

// O Ziggy injeta `route()` global; aqui basta uma URL previsível com os parâmetros.
vi.stubGlobal('route', (nome?: string, params?: unknown) => {
    if (!nome) return { current: () => false };
    const extra = params === undefined ? '' : `/${typeof params === 'object' ? Object.values(params as object).join('/') : params}`;
    return `/${nome.replaceAll('.', '/')}${extra}`;
});

// jsdom não tem estas APIs, e os Radix/sidebar dependem delas.
Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: (query: string) => ({
        matches: false,
        media: query,
        addEventListener: () => {},
        removeEventListener: () => {},
        addListener: () => {},
        removeListener: () => {},
        dispatchEvent: () => false,
    }),
});
vi.stubGlobal(
    'ResizeObserver',
    class {
        observe() {}
        unobserve() {}
        disconnect() {}
    },
);
Element.prototype.scrollIntoView = () => {};
Element.prototype.hasPointerCapture = () => false;

beforeEach(() => {
    setPage();
    for (const k of Object.keys(formErrors)) delete formErrors[k];
});
afterEach(cleanup);
