import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';

import AvatarUpload from './avatar-upload';
import PainelArquivos from './painel-arquivos';
import PainelLogoCertificado from './painel-logo-certificado';
import PainelRegulamento from './painel-regulamento';

const arquivo = (nome: string) => new File(['x'], nome, { type: 'application/pdf' });

describe('PainelRegulamento', () => {
    it('sem arquivo: orienta e deixa o envio desabilitado até escolher um', async () => {
        render(<PainelRegulamento regulamento={{ nome_arquivo: null, atualizado_em: null }} />);

        expect(screen.getByText(/Nenhum arquivo anexado/)).toBeTruthy();
        const enviar = screen.getByRole('button', { name: 'Anexar' }) as HTMLButtonElement;
        expect(enviar.disabled).toBe(true);

        await userEvent.upload(screen.getByLabelText('Escolher PDF'), arquivo('edital.pdf'));
        expect(enviar.disabled).toBe(false);

        await userEvent.click(enviar);
        expect(visits).toEqual([{ method: 'post', url: '/painel/evento/regulamento/upload', data: expect.anything() }]);
    });

    it('com arquivo: mostra nome e oferece substituir', () => {
        render(<PainelRegulamento regulamento={{ nome_arquivo: 'edital.pdf', atualizado_em: '10/05/2026' }} />);

        expect(screen.getByText('edital.pdf')).toBeTruthy();
        expect(screen.getByText(/Atualizado em 10\/05\/2026/)).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Substituir' })).toBeTruthy();
    });

    it('mostra o erro do servidor ligado ao campo', () => {
        formErrors.regulamento = 'O arquivo deve ser um PDF.';
        render(<PainelRegulamento regulamento={{ nome_arquivo: null, atualizado_em: null }} />);

        expect(screen.getByText('O arquivo deve ser um PDF.')).toBeTruthy();
        expect(screen.getByLabelText('Escolher PDF').getAttribute('aria-describedby')).toBe('regulamento-erro');
    });
});

describe('PainelLogoCertificado', () => {
    it('alterna o texto conforme já existe logo', () => {
        const { rerender } = render(<PainelLogoCertificado certificadoLogo={{ tem_logo: false }} />);
        expect(screen.getByText(/Nenhum logo enviado/)).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Anexar' })).toBeTruthy();

        rerender(<PainelLogoCertificado certificadoLogo={{ tem_logo: true }} />);
        expect(screen.getByText('Logo enviado')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Substituir' })).toBeTruthy();
    });

    it('envia o logo escolhido', async () => {
        render(<PainelLogoCertificado certificadoLogo={{ tem_logo: false }} />);
        await userEvent.upload(screen.getByLabelText('Escolher logo'), new File(['x'], 'logo.png', { type: 'image/png' }));
        await userEvent.click(screen.getByRole('button', { name: 'Anexar' }));

        expect(visits[0].url).toBe('/painel/evento/certificado/logo/upload');
    });
});

describe('PainelArquivos', () => {
    const itens = [
        { id: 7, nome: 'slides.pdf', tamanho: '1,2 MB', versao: 1, pode_remover: true },
        { id: 8, nome: 'codigo.zip', tamanho: '3 MB', versao: 1, pode_remover: false },
    ];

    it('lista arquivos com download e remove só o que pode', async () => {
        render(<PainelArquivos arquivos={{ itens, limite: 5 }} podeAnexar />);

        expect(screen.getByRole('link', { name: 'Baixar slides.pdf' }).getAttribute('href')).toBe('/submission-files/download/7');
        expect(screen.queryByRole('button', { name: 'Remover codigo.zip' })).toBeNull();

        await userEvent.click(screen.getByRole('button', { name: 'Remover slides.pdf' }));
        expect(visits).toEqual([{ method: 'delete', url: '/submission-files/destroy/7', data: undefined }]);
    });

    it('janela fechada: some o formulário', () => {
        render(<PainelArquivos arquivos={{ itens: [], limite: 5 }} podeAnexar={false} />);
        expect(screen.queryByLabelText('Escolher arquivo')).toBeNull();
        expect(screen.getByText(/Nenhum arquivo anexado/)).toBeTruthy();
    });

    it('limite atingido: troca o formulário por aviso', () => {
        render(<PainelArquivos arquivos={{ itens, limite: 2 }} podeAnexar />);
        expect(screen.getByText(/Limite de 2 arquivos atingido/)).toBeTruthy();
        expect(screen.queryByLabelText('Escolher arquivo')).toBeNull();
    });

    it('anexa o arquivo escolhido', async () => {
        render(<PainelArquivos arquivos={{ itens: [], limite: 5 }} podeAnexar />);
        await userEvent.upload(screen.getByLabelText('Escolher arquivo'), arquivo('a.pdf'));
        await userEvent.click(screen.getByRole('button', { name: 'Anexar' }));

        expect(visits[0].url).toBe('/submission-files/store');
    });
});

describe('AvatarUpload', () => {
    it('sem foto: iniciais e nenhum botão de remover', () => {
        render(<AvatarUpload nome="Maria Souza" avatarUrl={null} />);
        expect(screen.getByText('MS')).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Remover' })).toBeNull();
    });

    it('envia ao escolher o arquivo', async () => {
        render(<AvatarUpload nome="Maria Souza" avatarUrl={null} />);
        await userEvent.upload(screen.getByLabelText('Foto de perfil'), new File(['x'], 'eu.png', { type: 'image/png' }));

        expect(visits[0]).toMatchObject({ method: 'post', url: '/profile/avatar/update' });
    });

    it('com foto: permite remover', async () => {
        render(<AvatarUpload nome="Maria Souza" avatarUrl="/storage/a.png" />);
        await userEvent.click(screen.getByRole('button', { name: 'Remover' }));

        expect(visits[0]).toMatchObject({ method: 'delete', url: '/profile/avatar/destroy' });
    });
});
