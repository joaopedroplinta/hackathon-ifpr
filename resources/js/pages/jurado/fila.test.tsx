import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { setPage } from '@/test/inertia';

import FilaJurado from './fila';

const submissao = (id: number, enviada: boolean) => ({ submission_id: id, titulo: `Projeto ${id}`, equipe: `Equipe ${id}`, enviada });

describe('FilaJurado', () => {
    it('mostra o primeiro nome e o progresso', () => {
        setPage({ auth: { user: { name: 'Maria Souza' } } });
        render(<FilaJurado submissoes={[submissao(1, true), submissao(2, false)]} progresso={{ avaliadas: 1, total: 2 }} />);

        expect(screen.getByRole('heading', { name: 'Olá, Maria.' })).toBeTruthy();
        expect(screen.getByText(/1 de 2 avaliações enviadas/)).toBeTruthy();
        expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('50');
    });

    it('marca avaliada e pendente e linka para a avaliação', () => {
        render(<FilaJurado submissoes={[submissao(1, true), submissao(2, false)]} progresso={{ avaliadas: 1, total: 2 }} />);

        expect(screen.getByText('Avaliada')).toBeTruthy();
        expect(screen.getByText('Pendente')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Projeto 2/ }).getAttribute('href')).toBe('/jurado/avaliar/show/2');
    });

    it('explica quando nada foi atribuído', () => {
        render(<FilaJurado submissoes={[]} progresso={{ avaliadas: 0, total: 0 }} />);

        expect(screen.getByText('Nenhuma submissão atribuída a você ainda.')).toBeTruthy();
        expect(screen.queryByRole('progressbar')).toBeNull();
    });
});
