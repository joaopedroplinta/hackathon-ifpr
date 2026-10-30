import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import Status from './status';

describe('Status', () => {
    it('mostra o texto junto com um ícone decorativo, nunca só a cor', () => {
        const { container } = render(<Status tom="erro">Reprovado</Status>);

        expect(screen.getByText('Reprovado').textContent).toBe('Reprovado');
        expect(container.querySelector('svg')?.getAttribute('aria-hidden')).toBe('true');
    });

    it('aplica a classe do tom escolhido', () => {
        const { container } = render(<Status tom="sucesso">Aprovado</Status>);
        expect((container.firstElementChild as HTMLElement).className).toContain('text-emerald-700');
    });
});
