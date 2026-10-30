import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { Breadcrumbs } from './breadcrumbs';

describe('Breadcrumbs', () => {
    it('o último item é a página atual e os outros são links', () => {
        render(
            <Breadcrumbs
                breadcrumbs={[
                    { title: 'Painel', href: '/painel' },
                    { title: 'Equipes', href: '/painel/equipes' },
                ]}
            />,
        );

        expect(screen.getByRole('link', { name: 'Painel' }).getAttribute('href')).toBe('/painel');
        expect(screen.getByText('Equipes').getAttribute('aria-current')).toBe('page');
        expect(screen.getByText('Equipes').getAttribute('aria-disabled')).toBe('true');
    });

    it('não renderiza com lista vazia', () => {
        const { container } = render(<Breadcrumbs breadcrumbs={[]} />);
        expect(container.firstChild).toBeNull();
    });
});
