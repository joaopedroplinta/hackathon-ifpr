import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import CampoSenha from './campo-senha';

describe('CampoSenha', () => {
    it('começa oculto e alterna ao clicar no botão', async () => {
        render(<CampoSenha aria-label="Senha" defaultValue="segredo" />);
        const campo = screen.getByLabelText('Senha') as HTMLInputElement;

        expect(campo.type).toBe('password');

        await userEvent.click(screen.getByRole('button', { name: 'Mostrar senha' }));
        expect(campo.type).toBe('text');
        expect(screen.getByRole('button', { name: 'Ocultar senha' }).getAttribute('aria-pressed')).toBe('true');

        await userEvent.click(screen.getByRole('button', { name: 'Ocultar senha' }));
        expect(campo.type).toBe('password');
    });

    it('desabilita o botão junto com o campo', () => {
        render(<CampoSenha aria-label="Senha" disabled />);
        expect((screen.getByRole('button', { name: 'Mostrar senha' }) as HTMLButtonElement).disabled).toBe(true);
    });
});
