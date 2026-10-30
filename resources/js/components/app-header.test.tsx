import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { AppContent } from './app-content';
import { AppHeader } from './app-header';
import AppLogo from './app-logo';
import AppLogoIcon from './app-logo-icon';
import { AppShell } from './app-shell';
import { SidebarProvider } from './ui/sidebar';

describe('AppHeader', () => {
    it('mostra logo, atalhos externos e o avatar do usuário', () => {
        render(<AppHeader />);

        expect(screen.getAllByText('Hackathon IFPR').length).toBeGreaterThan(0);
        expect(screen.getAllByText('MS').length).toBeGreaterThan(0);
        expect(screen.getByRole('link', { name: 'Repositório' }).getAttribute('rel')).toContain('noopener');
        expect(screen.getByRole('link', { name: 'Regulamento' }).getAttribute('href')).toBe('/regulamento');
    });

    it('só mostra a trilha com mais de um nível', () => {
        const { rerender } = render(<AppHeader breadcrumbs={[{ title: 'Início', href: '/' }]} />);
        expect(screen.queryByRole('navigation', { name: 'breadcrumb' })).toBeNull();

        rerender(
            <AppHeader
                breadcrumbs={[
                    { title: 'Início', href: '/' },
                    { title: 'Equipes', href: '/equipes' },
                ]}
            />,
        );
        expect(screen.getByRole('navigation', { name: 'breadcrumb' })).toBeTruthy();
    });
});

describe('AppShell', () => {
    it('variante header é só um contêiner', () => {
        render(<AppShell>conteúdo</AppShell>);
        expect(screen.getByText('conteúdo')).toBeTruthy();
    });

    it('variante sidebar lembra o estado no localStorage', () => {
        localStorage.setItem('sidebar', 'false');
        render(
            <AppShell variant="sidebar">
                <p>dentro</p>
            </AppShell>,
        );
        expect(screen.getByText('dentro')).toBeTruthy();
        localStorage.clear();
    });
});

describe('AppContent', () => {
    it('header usa <main>; sidebar usa o inset', () => {
        const { rerender } = render(<AppContent>a</AppContent>);
        expect(screen.getByRole('main').textContent).toBe('a');

        rerender(
            <SidebarProvider>
                <AppContent variant="sidebar">b</AppContent>
            </SidebarProvider>,
        );
        expect(screen.getByRole('main').className).toContain('min-w-0');
    });
});

describe('Logo', () => {
    it('AppLogo mostra o nome e o ícone', () => {
        const { container } = render(<AppLogo />);
        expect(screen.getByText('Hackathon IFPR')).toBeTruthy();
        expect(container.querySelector('svg')).not.toBeNull();
    });

    it('AppLogoIcon repassa props para o svg', () => {
        const { container } = render(<AppLogoIcon data-teste="x" />);
        expect(container.querySelector('svg')?.getAttribute('data-teste')).toBe('x');
    });
});
