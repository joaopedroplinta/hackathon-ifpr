import { render, screen } from '@testing-library/react';
import { Home } from 'lucide-react';
import { describe, expect, it } from 'vitest';

import { SidebarProvider } from '@/components/ui/sidebar';
import { setPage } from '@/test/inertia';

import { NavFooter } from './nav-footer';
import { NavMain } from './nav-main';

const itens = [
    { title: 'Painel', url: '/painel', icon: Home },
    { title: 'Evento', url: '/painel/evento', icon: Home },
];

const comSidebar = (ui: React.ReactNode) => <SidebarProvider>{ui}</SidebarProvider>;

describe('NavMain', () => {
    it('marca só o item mais específico como ativo', () => {
        setPage({}, '/painel/evento/editar?aba=1');
        render(comSidebar(<NavMain items={itens} />));

        expect(screen.getByRole('link', { name: 'Evento' }).getAttribute('aria-current')).toBe('page');
        expect(screen.getByRole('link', { name: 'Painel' }).getAttribute('aria-current')).toBeNull();
    });

    it('não marca nada fora dos itens', () => {
        setPage({}, '/outra');
        render(comSidebar(<NavMain items={itens} />));
        expect(screen.queryAllByRole('link').filter((l) => l.getAttribute('aria-current'))).toHaveLength(0);
    });
});

describe('NavFooter', () => {
    it('link externo abre em nova aba e interno usa Link', () => {
        render(
            comSidebar(
                <NavFooter
                    items={[
                        { title: 'Repositório', url: 'https://github.com/x/y' },
                        { title: 'Ajuda', url: '/ajuda' },
                    ]}
                />,
            ),
        );

        const externo = screen.getByRole('link', { name: 'Repositório' });
        expect(externo.getAttribute('target')).toBe('_blank');
        expect(externo.getAttribute('rel')).toContain('noopener');
        expect(screen.getByRole('link', { name: 'Ajuda' }).getAttribute('target')).toBeNull();
    });
});
