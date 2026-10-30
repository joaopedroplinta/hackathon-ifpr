import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import EstadoVazio from './estado-vazio';

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children }: { href: string; children: React.ReactNode }) => <a href={href}>{children}</a>,
}));

describe('EstadoVazio', () => {
    it('mostra título e descrição', () => {
        render(<EstadoVazio titulo="Você ainda não tem equipe" descricao="Crie uma para começar." />);

        expect(screen.getByText('Você ainda não tem equipe')).toBeTruthy();
        expect(screen.getByText('Crie uma para começar.')).toBeTruthy();
    });

    it('oferece a próxima ação como link', () => {
        render(<EstadoVazio titulo="Sem equipe" acao={{ href: '/equipe/criar', texto: 'Criar equipe' }} />);

        expect(screen.getByRole('link', { name: 'Criar equipe' }).getAttribute('href')).toBe('/equipe/criar');
    });

    it('não renderiza link quando não há ação', () => {
        render(<EstadoVazio titulo="Nada por aqui" />);
        expect(screen.queryByRole('link')).toBeNull();
    });
});
