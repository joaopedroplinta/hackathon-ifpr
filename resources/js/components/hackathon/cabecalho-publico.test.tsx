import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { setPage } from '@/test/inertia';

import CabecalhoPublico from './cabecalho-publico';

describe('CabecalhoPublico', () => {
    it('visitante vê Entrar e Criar conta', () => {
        setPage({ auth: { user: null } });
        render(<CabecalhoPublico />);

        expect(screen.getAllByRole('link', { name: 'Entrar' }).length).toBeGreaterThan(0);
        expect(screen.getAllByRole('link', { name: /Criar conta/ })[0].getAttribute('href')).toBe('/register');
        expect(screen.queryByRole('link', { name: /Meu painel/ })).toBeNull();
    });

    it('logado vê o atalho para o painel', () => {
        render(<CabecalhoPublico />);

        expect(screen.getAllByRole('link', { name: /Meu painel/ })[0].getAttribute('href')).toBe('/dashboard');
        expect(screen.queryByRole('link', { name: 'Entrar' })).toBeNull();
    });

    it('tem os links públicos e o de pular para o conteúdo', () => {
        render(<CabecalhoPublico />);

        expect(screen.getByRole('link', { name: 'Pular para o conteúdo' }).getAttribute('href')).toBe('#conteudo-publico');
        expect(screen.getByRole('link', { name: 'Agenda' }).getAttribute('href')).toBe('/agenda/index');
        expect(screen.getByRole('link', { name: 'Resultados' })).toBeTruthy();
    });
});
