import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';

import ConfirmPassword from './confirm-password';
import ForgotPassword from './forgot-password';
import Login from './login';
import Register from './register';
import ResetPassword from './reset-password';
import VerifyEmail from './verify-email';

describe('Login', () => {
    it('envia e-mail, senha e "manter conectado"', async () => {
        render(<Login canResetPassword />);

        await userEvent.type(screen.getByLabelText('E-mail'), 'ana@ifpr.edu.br');
        await userEvent.type(screen.getByLabelText('Senha'), 'Segredo!1');
        await userEvent.click(screen.getByRole('checkbox', { name: 'Manter conectado' }));
        await userEvent.click(screen.getByRole('button', { name: 'Entrar' }));

        expect(visits).toEqual([{ method: 'post', url: '/login', data: { email: 'ana@ifpr.edu.br', password: 'Segredo!1', remember: true } }]);
    });

    it('mostra o link de recuperação só quando permitido', () => {
        const { rerender } = render(<Login canResetPassword />);
        expect(screen.getByRole('link', { name: 'Esqueceu a senha?' }).getAttribute('href')).toBe('/password/request');

        rerender(<Login canResetPassword={false} />);
        expect(screen.queryByRole('link', { name: 'Esqueceu a senha?' })).toBeNull();
    });

    it('oferece Google e cadastro; mostra o status quando existe', () => {
        render(<Login canResetPassword status="Senha redefinida." />);

        expect(screen.getByRole('link', { name: 'Entrar com Google' })).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Cadastre-se' }).getAttribute('href')).toBe('/register');
        expect(screen.getByRole('status').textContent).toContain('Senha redefinida.');
    });

    it('mostra o erro de credenciais', () => {
        formErrors.email = 'Credenciais inválidas.';
        render(<Login canResetPassword />);
        expect(screen.getAllByText('Credenciais inválidas.').length).toBeGreaterThan(0);
    });
});

describe('Register', () => {
    it('envia os quatro campos', async () => {
        render(<Register />);

        await userEvent.type(screen.getByLabelText('Nome'), 'Ana Lima');
        await userEvent.type(screen.getByLabelText('E-mail'), 'ana@ifpr.edu.br');
        await userEvent.type(screen.getByLabelText('Senha'), 'Segredo!1');
        await userEvent.type(screen.getByLabelText('Confirmar senha'), 'Segredo!1');
        await userEvent.click(screen.getByRole('button', { name: 'Criar conta' }));

        expect(visits).toEqual([
            {
                method: 'post',
                url: '/register',
                data: { name: 'Ana Lima', email: 'ana@ifpr.edu.br', password: 'Segredo!1', password_confirmation: 'Segredo!1' },
            },
        ]);
    });

    it('lista os requisitos da senha até ela ficar forte', async () => {
        render(<Register />);
        expect(screen.getByText('Pelo menos 8 caracteres')).toBeTruthy();

        await userEvent.type(screen.getByLabelText('Senha'), 'Segredo!1');
        expect(screen.queryByText('Pelo menos 8 caracteres')).toBeNull();
    });

    it('linka a política de privacidade e o login', () => {
        render(<Register />);
        expect(screen.getByRole('link', { name: 'Política de Privacidade' }).getAttribute('href')).toBe('/privacidade/show');
        expect(screen.getByRole('link', { name: 'Entrar' }).getAttribute('href')).toBe('/login');
    });
});

describe('ForgotPassword', () => {
    it('pede o link de redefinição', async () => {
        render(<ForgotPassword />);

        await userEvent.type(screen.getByLabelText('E-mail'), 'ana@ifpr.edu.br');
        await userEvent.click(screen.getByRole('button', { name: 'Enviar link de redefinição' }));

        expect(visits).toEqual([{ method: 'post', url: '/password/email', data: { email: 'ana@ifpr.edu.br' } }]);
    });

    it('confirma o envio quando há status', () => {
        render(<ForgotPassword status="Enviamos o link." />);
        expect(screen.getByRole('status').textContent).toContain('Enviamos o link.');
    });
});

describe('ResetPassword', () => {
    it('leva token e e-mail da URL e deixa o e-mail somente leitura', async () => {
        render(<ResetPassword token="tok" email="ana@ifpr.edu.br" />);

        expect((screen.getByLabelText('E-mail') as HTMLInputElement).readOnly).toBe(true);

        await userEvent.type(screen.getByLabelText('Nova senha'), 'Segredo!1');
        await userEvent.type(screen.getByLabelText('Confirmar senha'), 'Segredo!1');
        await userEvent.click(screen.getByRole('button', { name: 'Redefinir senha' }));

        expect(visits[0]).toEqual({
            method: 'post',
            url: '/password/store',
            data: { token: 'tok', email: 'ana@ifpr.edu.br', password: 'Segredo!1', password_confirmation: 'Segredo!1' },
        });
    });
});

describe('ConfirmPassword', () => {
    it('envia a senha para confirmar', async () => {
        render(<ConfirmPassword />);

        await userEvent.type(screen.getByLabelText('Senha'), 'Segredo!1');
        await userEvent.click(screen.getByRole('button', { name: 'Confirmar senha' }));

        expect(visits).toEqual([{ method: 'post', url: '/password/confirm', data: { password: 'Segredo!1' } }]);
    });
});

describe('VerifyEmail', () => {
    it('reenvia o e-mail de verificação', async () => {
        render(<VerifyEmail />);
        await userEvent.click(screen.getByRole('button', { name: 'Reenviar e-mail de verificação' }));

        expect(visits[0]).toMatchObject({ method: 'post', url: '/verification/send' });
        expect(screen.queryByRole('status')).toBeNull();
    });

    it('avisa quando o novo link foi enviado', () => {
        render(<VerifyEmail status="verification-link-sent" />);
        expect(screen.getByRole('status').textContent).toContain('Enviamos um novo link');
    });

    it('permite sair da conta', () => {
        render(<VerifyEmail />);
        expect(screen.getByRole('link', { name: 'Sair desta conta' }).getAttribute('href')).toBe('/logout');
    });
});
