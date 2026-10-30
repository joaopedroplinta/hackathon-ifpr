import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, setPage, visits } from '@/test/inertia';

import Appearance from './appearance';
import Password from './password';
import Profile from './profile';

const identidade = (extra = {}) => ({ cpf: null, tipo_vinculo: null, matricula_suap: null, matricula_siape: null, ...extra });

describe('Appearance', () => {
    it('oferece os três temas', () => {
        render(<Appearance />);

        expect(screen.getByRole('radio', { name: 'Claro' })).toBeTruthy();
        expect(screen.getByRole('radio', { name: 'Escuro' })).toBeTruthy();
        expect(screen.getByRole('radio', { name: 'Sistema' })).toBeTruthy();
    });
});

describe('Password', () => {
    it('envia as três senhas por PUT', async () => {
        render(<Password />);

        await userEvent.type(screen.getByLabelText('Senha atual'), 'Antiga!1');
        await userEvent.type(screen.getByLabelText('Nova senha'), 'Nova!Senha1');
        await userEvent.type(screen.getByLabelText('Confirmar senha'), 'Nova!Senha1');
        await userEvent.click(screen.getByRole('button', { name: 'Salvar senha' }));

        expect(visits[0]).toEqual({
            method: 'put',
            url: '/password/update',
            data: { current_password: 'Antiga!1', password: 'Nova!Senha1', password_confirmation: 'Nova!Senha1' },
        });
    });

    it('mostra erro da senha atual', () => {
        formErrors.current_password = 'A senha atual está incorreta.';
        render(<Password />);
        expect(screen.getAllByText('A senha atual está incorreta.').length).toBeGreaterThan(0);
    });
});

describe('Profile', () => {
    const props = { mustVerifyEmail: false, temSenha: true, identidade: identidade() };

    it('perfil incompleto avisa o que falta para o certificado', () => {
        render(<Profile {...props} />);
        expect(screen.getByText('Complete os dados do certificado')).toBeTruthy();
    });

    it('perfil externo com CPF fica pronto para o certificado', () => {
        render(<Profile {...props} identidade={identidade({ cpf: '12345678900', tipo_vinculo: 'externo' })} />);
        expect(screen.getByText('Perfil pronto para o certificado')).toBeTruthy();
    });

    it('aluno do IFPR sem matrícula continua incompleto e pede o SUAP', () => {
        render(<Profile {...props} identidade={identidade({ cpf: '12345678900', tipo_vinculo: 'aluno_ifpr' })} />);

        expect(screen.getByText('Complete os dados do certificado')).toBeTruthy();
        expect(screen.getByLabelText('Matrícula do SUAP')).toBeTruthy();
        expect(screen.queryByLabelText('Matrícula SIAPE')).toBeNull();
    });

    it('professor pede o SIAPE e, preenchido, libera o certificado', () => {
        render(<Profile {...props} identidade={identidade({ cpf: '1', tipo_vinculo: 'professor_ifpr', matricula_siape: '123' })} />);

        expect(screen.getByLabelText('Matrícula SIAPE')).toBeTruthy();
        expect(screen.getByText('Perfil pronto para o certificado')).toBeTruthy();
    });

    it('formata o CPF para leitura mas envia só dígitos', async () => {
        render(<Profile {...props} />);
        const cpf = screen.getByLabelText('CPF') as HTMLInputElement;

        await userEvent.type(cpf, '12345678900');
        expect(cpf.value).toBe('123.456.789-00');

        await userEvent.click(screen.getByRole('button', { name: 'Salvar alterações' }));
        expect(visits[0]).toMatchObject({ method: 'patch', url: '/profile/update', data: { cpf: '12345678900' } });
    });

    it('pede a senha atual só ao trocar o e-mail de quem tem senha', async () => {
        render(<Profile {...props} />);
        expect(screen.queryByLabelText('Senha atual')).toBeNull();

        const email = screen.getByLabelText('E-mail');
        await userEvent.clear(email);
        await userEvent.type(email, 'novo@ifpr.edu.br');
        expect(screen.getByLabelText('Senha atual')).toBeTruthy();
    });

    it('conta só Google (sem senha) nunca pede a senha atual', async () => {
        render(<Profile {...props} temSenha={false} />);
        const email = screen.getByLabelText('E-mail');
        await userEvent.clear(email);
        await userEvent.type(email, 'novo@ifpr.edu.br');

        expect(screen.queryByLabelText('Senha atual')).toBeNull();
    });

    it('e-mail não verificado oferece reenviar a confirmação', () => {
        setPage({ auth: { user: { email_verified_at: null } } });
        render(<Profile {...props} mustVerifyEmail status="verification-link-sent" />);

        expect(screen.getByText(/Seu e-mail ainda não foi confirmado/)).toBeTruthy();
        expect(screen.getByText('Um novo link de confirmação foi enviado para o seu e-mail.')).toBeTruthy();
    });

    it('e-mail verificado não mostra o aviso', () => {
        render(<Profile {...props} mustVerifyEmail />);
        expect(screen.queryByText(/Seu e-mail ainda não foi confirmado/)).toBeNull();
    });

    it('inclui a exclusão de conta', () => {
        render(<Profile {...props} />);
        expect(screen.getByText('Esta ação não pode ser desfeita.')).toBeTruthy();
    });
});
