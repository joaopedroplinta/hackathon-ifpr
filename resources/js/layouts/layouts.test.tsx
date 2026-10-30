import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { setPage } from '@/test/inertia';

import AppLayout from './app-layout';
import AppHeaderLayout from './app/app-header-layout';
import AppSidebarLayout from './app/app-sidebar-layout';
import AuthLayout from './auth-layout';
import AuthCardLayout from './auth/auth-card-layout';
import PublicLayout from './public-layout';
import SettingsLayout from './settings/layout';

describe('AppLayout', () => {
    it('envolve o conteúdo com navegação, atalho de pulo e trilha', () => {
        render(
            <AppLayout
                breadcrumbs={[
                    { title: 'Início', href: '/dashboard' },
                    { title: 'Equipe', href: '/equipe' },
                ]}
            >
                <p>conteúdo da página</p>
            </AppLayout>,
        );

        expect(screen.getByText('conteúdo da página')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Pular para o conteúdo' }).getAttribute('href')).toBe('#conteudo');
        expect(screen.getByRole('navigation', { name: 'breadcrumb' })).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Alternar navegação' })).toBeTruthy();
    });

    it('variante com cabeçalho também renderiza o conteúdo', () => {
        render(
            <AppHeaderLayout>
                <p>só cabeçalho</p>
            </AppHeaderLayout>,
        );
        expect(screen.getByText('só cabeçalho')).toBeTruthy();
    });

    it('sidebar layout sem trilha não quebra', () => {
        render(
            <AppSidebarLayout>
                <p>x</p>
            </AppSidebarLayout>,
        );
        expect(screen.getByText('x')).toBeTruthy();
    });
});

describe('AuthLayout', () => {
    it('mostra título, descrição e links legais', () => {
        render(
            <AuthLayout title="Entrar" description="Acesse sua conta">
                <form aria-label="login" />
            </AuthLayout>,
        );

        expect(screen.getByRole('heading', { level: 1, name: 'Entrar' })).toBeTruthy();
        expect(screen.getByText('Acesse sua conta')).toBeTruthy();
        expect(screen.getByRole('form', { name: 'login' })).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Privacidade' }).getAttribute('href')).toBe('/privacidade/show');
        expect(screen.getByRole('link', { name: 'Voltar ao evento' })).toBeTruthy();
    });

    it('card layout mostra título e descrição opcional', () => {
        const { rerender } = render(
            <AuthCardLayout title="Verificar" description="Confira o e-mail">
                x
            </AuthCardLayout>,
        );
        expect(screen.getByRole('heading', { name: 'Verificar' })).toBeTruthy();
        expect(screen.getByText('Confira o e-mail')).toBeTruthy();

        rerender(<AuthCardLayout title="Verificar">x</AuthCardLayout>);
        expect(screen.queryByText('Confira o e-mail')).toBeNull();
    });
});

describe('PublicLayout', () => {
    it('tem cabeçalho, título da página, ação e rodapé', () => {
        render(
            <PublicLayout titulo="Agenda" descricao="Programação" acao={<button>Baixar</button>}>
                <p>miolo</p>
            </PublicLayout>,
        );

        expect(screen.getByRole('heading', { level: 1, name: 'Agenda' })).toBeTruthy();
        expect(screen.getByText('Programação')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Baixar' })).toBeTruthy();
        expect(screen.getByText('miolo')).toBeTruthy();
        expect(screen.getByRole('contentinfo')).toBeTruthy();
        expect(screen.getByText('Hackathon IFPR · Campus Pinhais')).toBeTruthy();
    });

    it('aceita um contexto próprio', () => {
        render(
            <PublicLayout titulo="Edição 2025" contexto="Arquivo histórico">
                x
            </PublicLayout>,
        );
        expect(screen.getByText('Arquivo histórico')).toBeTruthy();
    });
});

describe('SettingsLayout', () => {
    it('marca a aba atual ignorando a query string', () => {
        setPage({}, '/settings/password?x=1');
        render(
            <SettingsLayout>
                <p>form</p>
            </SettingsLayout>,
        );

        expect(screen.getByRole('link', { name: 'Senha' }).getAttribute('aria-current')).toBe('page');
        expect(screen.getByRole('link', { name: 'Perfil' }).getAttribute('aria-current')).toBeNull();
        expect(screen.getByText('form')).toBeTruthy();
    });
});
