import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';

import DeleteUser from './delete-user';

const abrir = async () => {
    render(<DeleteUser />);
    await userEvent.click(screen.getByRole('button', { name: 'Excluir conta' }));
};

describe('DeleteUser', () => {
    it('avisa que a exclusão é definitiva antes de pedir a senha', async () => {
        render(<DeleteUser />);
        expect(screen.getByText('Esta ação não pode ser desfeita.')).toBeTruthy();
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('só exclui depois de digitar a senha e confirmar', async () => {
        await abrir();
        expect(visits).toHaveLength(0);

        await userEvent.type(screen.getByPlaceholderText('Senha'), 'segredo');
        const dialogo = screen.getByRole('dialog');
        await userEvent.click(dialogo.querySelector('button[type="submit"]') as HTMLElement);

        expect(visits).toEqual([{ method: 'delete', url: '/profile/destroy', data: { password: 'segredo' } }]);
    });

    it('mostra o erro de senha errada', async () => {
        formErrors.password = 'A senha está incorreta.';
        await abrir();
        expect(screen.getByText('A senha está incorreta.')).toBeTruthy();
    });

    it('cancelar fecha o diálogo sem enviar', async () => {
        await abrir();
        await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(screen.queryByRole('dialog')).toBeNull();
        expect(visits).toHaveLength(0);
    });
});
