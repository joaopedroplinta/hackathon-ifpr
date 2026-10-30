import { useState } from 'react';
import { vi } from 'vitest';

import { type SharedData } from '@/types';

/** Props compartilhadas padrão (jurado logado, sem flash). `setPage` sobrescreve por teste. */
const sharedPadrao = (): SharedData => ({
    name: 'Hackathon IFPR',
    app_version: 'v0.0.0',
    auth: {
        user: {
            id: 1,
            name: 'Maria Souza',
            email: 'maria@ifpr.edu.br',
            email_verified_at: '2026-01-01T00:00:00Z',
            created_at: '2026-01-01T00:00:00Z',
            updated_at: '2026-01-01T00:00:00Z',
        },
        is_staff: false,
        is_judge: false,
        is_admin: false,
        is_participante: true,
    },
    evento: { nome: 'Hackathon 2026', slug: 'hackathon-2026', inscricoes_abertas: true, inscrito: false },
    flash: {},
});

export const page: { props: Record<string, unknown>; url: string } = { props: sharedPadrao(), url: '/' };

/** Cada chamada de visita (post/put/delete/router.*) fica registrada para os testes conferirem. */
export const visits: { method: string; url: string; data?: unknown }[] = [];

export function setPage(props: Record<string, unknown> = {}, url = '/') {
    const base = sharedPadrao();
    page.props = { ...base, ...props, auth: { ...base.auth, ...((props.auth as object) ?? {}) } };
    page.url = url;
    visits.length = 0;
}

/** Erros de validação que o `useForm` devolve (o Laravel os manda em `errors`). */
export const formErrors: Record<string, string> = {};

const registrar = (method: string, url: string, data?: unknown) => visits.push({ method, url, data });

function useForm<T extends Record<string, unknown>>(inicial: T) {
    const [data, setDataState] = useState<T>(inicial);
    const metodo = (method: string) => (url: string, opcoes?: { onSuccess?: () => void }) => {
        registrar(method, url, data);
        opcoes?.onSuccess?.();
    };

    return {
        data,
        setData: (chave: keyof T | ((d: T) => T) | T, valor?: unknown) => {
            if (typeof chave === 'function') setDataState(chave as (d: T) => T);
            else if (typeof chave === 'object') setDataState(chave as T);
            else setDataState((d) => ({ ...d, [chave]: valor }));
        },
        errors: formErrors,
        processing: false,
        recentlySuccessful: false,
        wasSuccessful: false,
        isDirty: false,
        progress: null,
        hasErrors: Object.keys(formErrors).length > 0,
        reset: () => setDataState(inicial),
        clearErrors: vi.fn(),
        setError: vi.fn(),
        transform: vi.fn(),
        post: metodo('post'),
        put: metodo('put'),
        patch: metodo('patch'),
        delete: metodo('delete'),
        get: metodo('get'),
    };
}

export const router = {
    visit: (url: string) => registrar('visit', url),
    get: (url: string, data?: unknown) => registrar('get', url, data),
    post: (url: string, data?: unknown) => registrar('post', url, data),
    put: (url: string, data?: unknown) => registrar('put', url, data),
    patch: (url: string, data?: unknown) => registrar('patch', url, data),
    delete: (url: string) => registrar('delete', url),
    reload: () => registrar('reload', ''),
};

export const inertiaMock = {
    // Props próprias do Link do Inertia não existem em <a>: descartadas aqui para não vazar no DOM.
    Link: ({ href, children, ...resto }: Record<string, unknown> & { href: string; children?: React.ReactNode }) => {
        const { method, as, prefetch, preserveScroll, preserveState, replace, ...html } = resto;
        void [method, as, prefetch, preserveScroll, preserveState, replace];
        return (
            <a href={href} {...(html as object)}>
                {children}
            </a>
        );
    },
    Head: () => null,
    usePage: () => page,
    useForm,
    router,
    createInertiaApp: vi.fn(),
};
