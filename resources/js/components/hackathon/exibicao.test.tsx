import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { setPage } from '@/test/inertia';

import DocumentoPublico from './documento-publico';
import { GoogleLoginButton } from './google-login-button';
import HistoricoEnvios from './historico-envios';
import { CabecalhoPagina, ContainerPagina } from './pagina';
import Paginacao from './paginacao';
import RodapePublico from './rodape-publico';
import SecaoFormulario from './secao-formulario';

describe('DocumentoPublico', () => {
    it('numera o índice e linka para as âncoras', () => {
        render(
            <DocumentoPublico
                secoes={[
                    { id: 'a', titulo: 'Primeira' },
                    { id: 'b', titulo: 'Segunda' },
                ]}
            >
                <p>corpo</p>
            </DocumentoPublico>,
        );

        expect(screen.getByRole('link', { name: /Primeira/ }).getAttribute('href')).toBe('#a');
        expect(screen.getByText('02')).toBeTruthy();
        expect(screen.getByText('corpo')).toBeTruthy();
    });
});

describe('SecaoFormulario', () => {
    it('liga a seção ao título e mostra a instrução', () => {
        render(
            <SecaoFormulario titulo="Dados" instrucao="Preencha tudo.">
                <input aria-label="nome" />
            </SecaoFormulario>,
        );

        expect(screen.getByRole('region', { name: 'Dados' })).toBeTruthy();
        expect(screen.getByText('Preencha tudo.')).toBeTruthy();
    });

    it('sem instrução não renderiza parágrafo', () => {
        render(<SecaoFormulario titulo="Dados">x</SecaoFormulario>);
        expect(screen.queryByText('Preencha tudo.')).toBeNull();
    });
});

describe('GoogleLoginButton', () => {
    it('é um link comum (sai do domínio) com rótulo padrão ou customizado', () => {
        const { rerender } = render(<GoogleLoginButton />);
        const link = screen.getByRole('link', { name: 'Entrar com Google' });
        expect(link.getAttribute('href')).toBe('/google/redirect');

        rerender(<GoogleLoginButton label="Continuar com Google" />);
        expect(screen.getByRole('link', { name: 'Continuar com Google' })).toBeTruthy();
    });
});

describe('Paginacao', () => {
    const links = [
        { url: null, label: '&laquo; Anterior', active: false },
        { url: '/x?page=1', label: '1', active: true },
        { url: '/x?page=2', label: '2', active: false },
    ];

    it('não aparece com uma página só', () => {
        const { container } = render(<Paginacao links={links} lastPage={1} />);
        expect(container.firstChild).toBeNull();
    });

    it('marca a página atual e desabilita o que não tem url', () => {
        render(<Paginacao links={links} lastPage={2} />);

        expect(screen.getByRole('link', { name: '1' }).getAttribute('aria-current')).toBe('page');
        expect(screen.getByRole('link', { name: '2' }).getAttribute('aria-current')).toBeNull();
        expect(screen.getAllByRole('link')).toHaveLength(2);
        expect(screen.getByText('« Anterior')).toBeTruthy();
    });
});

describe('HistoricoEnvios', () => {
    it('orienta quando ainda não há envio', () => {
        render(<HistoricoEnvios versoes={[]} />);
        expect(screen.getByText(/Nenhum envio ainda/)).toBeTruthy();
    });

    it('lista versão, autor e data em horário de Brasília', () => {
        render(<HistoricoEnvios versoes={[{ versao: 2, autor: 'Ana', criado_em: '2026-05-10T15:30:00Z' }]} />);

        expect(screen.getByText('Versão 2')).toBeTruthy();
        expect(screen.getByText(/Ana · 10\/05\/2026,? 12:30/)).toBeTruthy();
    });
});

describe('Página', () => {
    it('container aplica a largura pedida', () => {
        const { container } = render(<ContainerPagina largura="leitura">x</ContainerPagina>);
        expect((container.firstChild as HTMLElement).className).toContain('max-w-3xl');
    });

    it('cabeçalho mostra eyebrow, descrição e ação só quando vêm', () => {
        const { rerender } = render(<CabecalhoPagina titulo="Equipes" eyebrow="Painel" descricao="Lista" acao={<button>Nova</button>} />);
        expect(screen.getByRole('heading', { level: 1, name: 'Equipes' })).toBeTruthy();
        expect(screen.getByText('Painel')).toBeTruthy();
        expect(screen.getByText('Lista')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Nova' })).toBeTruthy();

        rerender(<CabecalhoPagina titulo="Equipes" />);
        expect(screen.queryByText('Painel')).toBeNull();
        expect(screen.queryByRole('button')).toBeNull();
    });
});

describe('RodapePublico', () => {
    it('mostra os links públicos e a versão', () => {
        setPage({ app_version: 'v1.3.4' });
        render(<RodapePublico />);

        expect(screen.getByRole('link', { name: 'Agenda' })).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Privacidade' })).toBeTruthy();
        expect(screen.getByText('v1.3.4')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Repositório no GitHub' }).getAttribute('rel')).toContain('noopener');
    });
});
