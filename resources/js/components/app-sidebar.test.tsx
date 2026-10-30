import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { SidebarProvider } from '@/components/ui/sidebar';
import { setPage } from '@/test/inertia';

import { AppSidebar } from './app-sidebar';

const montar = (auth: Record<string, boolean>, url = '/dashboard') => {
    setPage({ auth, app_version: 'v9.9.9' }, url);
    render(
        <SidebarProvider>
            <AppSidebar />
        </SidebarProvider>,
    );
};

const papeis = { is_participante: false, is_judge: false, is_staff: false, is_admin: false };
const tem = (nome: string) => screen.queryByRole('link', { name: nome }) !== null;

describe('AppSidebar', () => {
    it('participante vê equipe e projeto, mas não painel nem avaliação', () => {
        montar({ ...papeis, is_participante: true });

        expect(tem('Minha equipe')).toBe(true);
        expect(tem('Meu projeto')).toBe(true);
        expect(tem('Avaliar')).toBe(false);
        expect(tem('Painel')).toBe(false);
        expect(tem('Usuários')).toBe(false);
    });

    it('jurado vê só a avaliação, sem atalhos de participante', () => {
        montar({ ...papeis, is_judge: true });

        expect(tem('Avaliar')).toBe(true);
        expect(tem('Minha equipe')).toBe(false);
        expect(tem('Painel')).toBe(false);
    });

    it('organizador vê o painel mas não a administração', () => {
        montar({ ...papeis, is_staff: true });

        expect(tem('Painel')).toBe(true);
        expect(tem('Emitir certificados')).toBe(true);
        expect(tem('Usuários')).toBe(false);
    });

    it('admin vê a administração', () => {
        montar({ ...papeis, is_staff: true, is_admin: true });
        expect(tem('Usuários')).toBe(true);
    });

    it('todo papel tem crachá, certificados e o rodapé', () => {
        montar(papeis);

        expect(tem('Crachá')).toBe(true);
        expect(tem('Certificados')).toBe(true);
        expect(tem('Regulamento')).toBe(true);
        expect(screen.getByRole('link', { name: 'Repositório' }).getAttribute('target')).toBe('_blank');
        expect(screen.getByText('v9.9.9')).toBeTruthy();
    });

    it('marca o item da página atual', () => {
        montar({ ...papeis, is_participante: true }, '/equipe/criar');
        expect(screen.getByRole('link', { name: 'Minha equipe' }).getAttribute('aria-current')).toBe('page');
    });

    it('menu do usuário oferece configurações e sair', async () => {
        montar(papeis);
        await userEvent.click(screen.getByRole('button', { name: /Maria Souza/ }));

        expect(await screen.findByRole('menuitem', { name: /Configurações/ })).toBeTruthy();
        expect(screen.getByRole('menuitem', { name: /Sair/ }).closest('a')?.getAttribute('href')).toBe('/logout');
    });
});
