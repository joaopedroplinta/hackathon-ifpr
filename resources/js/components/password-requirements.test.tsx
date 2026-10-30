import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import PasswordRequirements from './password-requirements';

describe('PasswordRequirements', () => {
    it('lista os quatro requisitos com senha vazia', () => {
        render(<PasswordRequirements senha="" />);
        expect(screen.getAllByRole('listitem')).toHaveLength(4);
    });

    it('marca como atendido só o que a senha cumpre', () => {
        render(<PasswordRequirements senha="abcdefgh" />);

        expect(screen.getByText('Pelo menos 8 caracteres').className).toContain('text-verde-ifpr');
        expect(screen.getByText('Uma letra minúscula').className).toContain('text-verde-ifpr');
        expect(screen.getByText('Uma letra maiúscula').className).toContain('text-muted-foreground');
        expect(screen.getByText('Um símbolo (ex.: !@#$%)').className).toContain('text-muted-foreground');
    });

    it('some quando todos os requisitos são atendidos', () => {
        const { container } = render(<PasswordRequirements senha="Abcdef1!" />);
        expect(container.firstChild).toBeNull();
    });
});
