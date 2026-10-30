import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, setPage, visits } from '@/test/inertia';

import PainelConvites from './painel-convites';

const convites = (extra = {}) => ({ pode_convidar: true, motivo_bloqueio: null, pendentes: [], ...extra });

describe('PainelConvites', () => {
    it('envia o convite com o e-mail digitado', async () => {
        setPage({ convites: convites() });
        render(<PainelConvites />);

        await userEvent.type(screen.getByLabelText('E-mail'), 'ana@ifpr.edu.br');
        await userEvent.click(screen.getByRole('button', { name: 'Convidar' }));

        expect(visits).toEqual([{ method: 'post', url: '/team-invites/store', data: { email: 'ana@ifpr.edu.br' } }]);
    });

    it('bloqueado: mostra o motivo no lugar do formulário', () => {
        setPage({ convites: convites({ pode_convidar: false, motivo_bloqueio: 'A equipe já está completa.' }) });
        render(<PainelConvites />);

        expect(screen.getByText('A equipe já está completa.')).toBeTruthy();
        expect(screen.queryByLabelText('E-mail')).toBeNull();
    });

    it('bloqueado sem motivo usa a mensagem genérica', () => {
        setPage({ convites: convites({ pode_convidar: false }) });
        render(<PainelConvites />);
        expect(screen.getByText('Você não pode convidar agora.')).toBeTruthy();
    });

    it('lista pendentes e distingue expirado', () => {
        setPage({
            convites: convites({
                pendentes: [
                    { id: 1, email: 'a@x.com', expira_em: '2099-01-01T12:00:00Z', expirado: false },
                    { id: 2, email: 'b@x.com', expira_em: '2020-01-01T12:00:00Z', expirado: true },
                ],
            }),
        });
        render(<PainelConvites />);

        expect(screen.getByText('a@x.com')).toBeTruthy();
        expect(screen.getByText(/Expira em 01\/01\/2099/)).toBeTruthy();
        expect(screen.getByText('Expirado')).toBeTruthy();
    });

    it('sem pendentes mostra o estado vazio', () => {
        setPage({ convites: convites() });
        render(<PainelConvites />);
        expect(screen.getByText('Nenhum convite pendente no momento.')).toBeTruthy();
    });

    it('mostra erro de validação do e-mail', () => {
        formErrors.email = 'E-mail inválido.';
        setPage({ convites: convites() });
        render(<PainelConvites />);
        expect(screen.getByText('E-mail inválido.')).toBeTruthy();
    });
});
