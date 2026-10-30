import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import Heading from './heading';
import HeadingSmall from './heading-small';

describe('Heading', () => {
    it('mostra título e descrição opcional', () => {
        const { rerender } = render(<Heading title="Perfil" description="Seus dados" />);
        expect(screen.getByRole('heading', { name: 'Perfil' })).toBeTruthy();
        expect(screen.getByText('Seus dados')).toBeTruthy();

        rerender(<Heading title="Perfil" />);
        expect(screen.queryByText('Seus dados')).toBeNull();
    });

    it('versão pequena segue a mesma regra', () => {
        const { rerender } = render(<HeadingSmall title="Senha" description="Troque aqui" />);
        expect(screen.getByRole('heading', { name: 'Senha' })).toBeTruthy();
        expect(screen.getByText('Troque aqui')).toBeTruthy();

        rerender(<HeadingSmall title="Senha" />);
        expect(screen.queryByText('Troque aqui')).toBeNull();
    });
});
