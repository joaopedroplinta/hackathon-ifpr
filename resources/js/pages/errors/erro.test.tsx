import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import Erro from './erro';

describe('Erro', () => {
    it.each([
        [403, 'Acesso não autorizado'],
        [404, 'Página não encontrada'],
        [500, 'Algo deu errado'],
        [503, 'Sistema em manutenção'],
    ] as const)('status %i mostra "%s" e o caminho de volta', (status, titulo) => {
        render(<Erro status={status} />);

        expect(screen.getByRole('heading', { level: 1, name: `${status} — ${titulo}` })).toBeTruthy();
        expect(screen.getByRole('link', { name: /Voltar para a página inicial/ }).getAttribute('href')).toBe('/home');
    });

    it('status desconhecido cai na mensagem de erro genérico', () => {
        render(<Erro status={418 as 500} />);
        expect(screen.getByRole('heading', { level: 1, name: /Algo deu errado/ })).toBeTruthy();
    });
});
