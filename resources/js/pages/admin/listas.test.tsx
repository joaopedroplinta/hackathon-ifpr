import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { visits } from '@/test/inertia';
import { type Paginated } from '@/types';
import { type LinhaEquipe, type LinhaSubmissao } from '@/types/admin';

import ListaEquipes from './equipes/index';
import ListaSubmissoes from './submissoes/index';

const pagina = <T,>(data: T[], extra: Partial<Paginated<T>> = {}): Paginated<T> => ({
    data,
    links: [
        { url: null, label: '&laquo; Anterior', active: false },
        { url: '/x?page=1', label: '1', active: true },
        { url: '/x?page=2', label: '2', active: false },
    ],
    current_page: 1,
    last_page: 1,
    from: 1,
    to: data.length,
    total: data.length,
    ...extra,
});

const equipe = (id: number, extra: Partial<LinhaEquipe> = {}): LinhaEquipe => ({
    id,
    nome: `Equipe ${id}`,
    trilha: null,
    lider: 'Ana',
    integrantes: 3,
    status: 'confirmed',
    status_label: 'Confirmada',
    submissao: null,
    pode_desclassificar: false,
    pode_reabilitar: false,
    ...extra,
});

describe('ListaEquipes', () => {
    const opcoes = { status: [{ valor: 'confirmed' as const, rotulo: 'Confirmada' }] };
    const filtros = { status: null, busca: null };

    it('sem equipes explica o vazio; com filtro sugere limpar', () => {
        const { unmount } = render(<ListaEquipes equipes={pagina<LinhaEquipe>([])} filtros={filtros} opcoes={opcoes} />);
        expect(screen.getByText('Nenhuma equipe ainda')).toBeTruthy();
        unmount();

        render(<ListaEquipes equipes={pagina<LinhaEquipe>([])} filtros={{ status: 'draft', busca: null }} opcoes={opcoes} />);
        expect(screen.getByText('Nenhuma equipe com esses filtros')).toBeTruthy();
    });

    it('lista equipe com trilha, líder, projeto e traço quando falta dado', () => {
        render(
            <ListaEquipes
                equipes={pagina([
                    equipe(1, { trilha: { nome: 'IA', cor: null }, submissao: { id: 8, titulo: 'App', status_label: 'Enviado' } }),
                    equipe(2, { lider: null, status: 'draft', status_label: 'Rascunho' }),
                ])}
                filtros={filtros}
                opcoes={opcoes}
            />,
        );

        expect(screen.getByText('2 equipes nesta edição.', { exact: false })).toBeTruthy();
        expect(screen.getByRole('link', { name: /App/ }).getAttribute('href')).toBe('/painel/submissions/show/8');
        expect(screen.getByText('Sem envio')).toBeTruthy();
        expect(screen.getAllByText('—').length).toBeGreaterThan(0);
    });

    it('busca e filtro por situação consultam o servidor sem perder o outro filtro', async () => {
        render(<ListaEquipes equipes={pagina([equipe(1)])} filtros={{ status: 'confirmed', busca: null }} opcoes={opcoes} />);

        await userEvent.type(screen.getByLabelText('Buscar por nome da equipe'), 'devs');
        await userEvent.click(screen.getByRole('button', { name: 'Buscar' }));
        expect(visits[0]).toEqual({ method: 'get', url: '/painel/teams/index', data: { status: 'confirmed', busca: 'devs' } });

        await userEvent.selectOptions(screen.getByLabelText('Situação'), '');
        expect(visits[1].data).toEqual({ busca: 'devs' });
    });

    it('limpar filtros zera a consulta', async () => {
        render(<ListaEquipes equipes={pagina([equipe(1)])} filtros={{ status: 'confirmed', busca: 'x' }} opcoes={opcoes} />);
        await userEvent.click(screen.getByRole('button', { name: 'Limpar filtros' }));

        expect(visits[0].data).toEqual({});
    });

    it('desclassifica com motivo, só depois de abrir o diálogo', async () => {
        render(<ListaEquipes equipes={pagina([equipe(7, { pode_desclassificar: true })])} filtros={filtros} opcoes={opcoes} />);
        expect(screen.queryByRole('button', { name: /Reabilitar/ })).toBeNull();

        await userEvent.click(screen.getByRole('button', { name: 'Desclassificar a equipe Equipe 7' }));
        const dialogo = screen.getByRole('dialog', { name: 'Desclassificar equipe' });
        await userEvent.type(within(dialogo).getByLabelText('Motivo (a equipe vai ler)'), 'Plágio');
        await userEvent.click(within(dialogo).getByRole('button', { name: 'Desclassificar' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/teams/disqualify/7', data: { motivo: 'Plágio' } }]);
    });

    it('reabilita pela rota própria', async () => {
        render(
            <ListaEquipes
                equipes={pagina([equipe(7, { status: 'disqualified', status_label: 'Desclassificada', pode_reabilitar: true })])}
                filtros={filtros}
                opcoes={opcoes}
            />,
        );
        await userEvent.click(screen.getByRole('button', { name: 'Reabilitar a equipe Equipe 7' }));
        await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Reabilitar' }));

        expect(visits[0].url).toBe('/painel/teams/reinstate/7');
    });

    it('cancelar não envia nada', async () => {
        render(<ListaEquipes equipes={pagina([equipe(7, { pode_desclassificar: true })])} filtros={filtros} opcoes={opcoes} />);
        await userEvent.click(screen.getByRole('button', { name: /Desclassificar a equipe/ }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(screen.queryByRole('dialog')).toBeNull();
        expect(visits).toHaveLength(0);
    });

    it('pagina só com mais de uma página', () => {
        const { unmount } = render(<ListaEquipes equipes={pagina([equipe(1)])} filtros={filtros} opcoes={opcoes} />);
        expect(screen.queryByRole('navigation', { name: 'Paginação' })).toBeNull();
        unmount();

        render(<ListaEquipes equipes={pagina([equipe(1)], { last_page: 2 })} filtros={filtros} opcoes={opcoes} />);
        expect(screen.getByRole('link', { name: '1' }).getAttribute('aria-current')).toBe('page');
    });
});

const linha = (id: number, extra: Partial<LinhaSubmissao> = {}): LinhaSubmissao => ({
    id,
    titulo: `Projeto ${id}`,
    equipe: { nome: `Equipe ${id}`, slug: `equipe-${id}` },
    trilha: null,
    status: 'submitted',
    status_label: 'Enviado',
    origem_label: 'Sistema',
    precisa_conferencia: false,
    enviado_em: '2026-05-10T15:30:00Z',
    versao_atual: 2,
    arquivos: 0,
    ...extra,
});

describe('ListaSubmissoes', () => {
    const opcoes = { status: [], trilhas: [{ id: 3, nome: 'IA' }] };
    const filtros = { status: null, track_id: null, busca: null };
    const resumo = (extra = {}) => ({
        total: 2,
        por_status: [
            { valor: 'submitted' as const, rotulo: 'Enviado', total: 1 },
            { valor: 'draft' as const, rotulo: 'Rascunho', total: 1 },
        ],
        equipes_sem_envio: [],
        ...extra,
    });

    it('mostra a data em horário de Brasília, versão, arquivos e "Sem título"', () => {
        render(
            <ListaSubmissoes
                submissoes={pagina([linha(1, { arquivos: 2 }), linha(2, { titulo: null, enviado_em: null, arquivos: 1 })])}
                filtros={filtros}
                opcoes={opcoes}
                resumo={resumo()}
            />,
        );

        expect(screen.getByText(/10\/05\/2026,? 12:30/)).toBeTruthy();
        expect(screen.getByText('2 arquivos')).toBeTruthy();
        expect(screen.getByText('1 arquivo')).toBeTruthy();
        expect(screen.getByText('Sem título')).toBeTruthy();
        expect(screen.getByText('2 projetos registrados nesta edição.')).toBeTruthy();
    });

    it('envio por fora do sistema fica marcado para conferência', () => {
        render(
            <ListaSubmissoes
                submissoes={pagina([linha(1, { precisa_conferencia: true, origem_label: 'Formulário' })])}
                filtros={filtros}
                opcoes={opcoes}
                resumo={resumo()}
            />,
        );
        expect(screen.getByText('Formulário — conferir')).toBeTruthy();
    });

    it('lista as equipes que ainda não enviaram (singular e plural)', () => {
        const { unmount } = render(
            <ListaSubmissoes submissoes={pagina([linha(1)])} filtros={filtros} opcoes={opcoes} resumo={resumo({ equipes_sem_envio: ['Alfa'] })} />,
        );
        expect(screen.getByText('1 equipe ainda não enviou')).toBeTruthy();
        unmount();

        render(
            <ListaSubmissoes
                submissoes={pagina([linha(1)])}
                filtros={filtros}
                opcoes={opcoes}
                resumo={resumo({ equipes_sem_envio: ['Alfa', 'Beta'] })}
            />,
        );
        expect(screen.getByText('2 equipes ainda não enviaram')).toBeTruthy();
        expect(screen.getByText('Alfa · Beta')).toBeTruthy();
    });

    it('clicar num total filtra por situação e clicar de novo desfaz', async () => {
        const { unmount } = render(<ListaSubmissoes submissoes={pagina([linha(1)])} filtros={filtros} opcoes={opcoes} resumo={resumo()} />);
        await userEvent.click(screen.getByRole('button', { name: /Rascunho/ }));
        expect(visits[0]).toEqual({ method: 'get', url: '/painel/submissions/index', data: { status: 'draft' } });
        unmount();

        render(<ListaSubmissoes submissoes={pagina([linha(1)])} filtros={{ ...filtros, status: 'draft' }} opcoes={opcoes} resumo={resumo()} />);
        const ativo = screen.getByRole('button', { name: /Rascunho/ });
        expect(ativo.getAttribute('aria-pressed')).toBe('true');
        await userEvent.click(ativo);
        expect(visits[1].data).toEqual({});
    });

    it('filtra por trilha', async () => {
        render(<ListaSubmissoes submissoes={pagina([linha(1)])} filtros={filtros} opcoes={opcoes} resumo={resumo()} />);
        await userEvent.selectOptions(screen.getByLabelText('Trilha'), '3');

        expect(visits[0].data).toEqual({ track_id: '3' });
    });

    it('exportar muda o rótulo conforme haja filtro e some sem resultados', () => {
        const { unmount } = render(<ListaSubmissoes submissoes={pagina([linha(1)])} filtros={filtros} opcoes={opcoes} resumo={resumo()} />);
        expect(screen.getByRole('link', { name: /Baixar tudo/ })).toBeTruthy();
        unmount();

        const { unmount: u2 } = render(
            <ListaSubmissoes submissoes={pagina([linha(1)])} filtros={{ ...filtros, busca: 'x' }} opcoes={opcoes} resumo={resumo()} />,
        );
        expect(screen.getByRole('link', { name: /Baixar filtrados/ })).toBeTruthy();
        u2();

        render(<ListaSubmissoes submissoes={pagina<LinhaSubmissao>([])} filtros={filtros} opcoes={opcoes} resumo={resumo({ total: 0 })} />);
        expect(screen.queryByRole('link', { name: /Baixar/ })).toBeNull();
        expect(screen.getByText('Nenhuma submissão ainda')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Lançar manualmente/ }).getAttribute('href')).toBe('/painel/submissions/record/create');
    });

    it('vazio com filtro é diferente do vazio sem filtro', () => {
        render(
            <ListaSubmissoes
                submissoes={pagina<LinhaSubmissao>([])}
                filtros={{ ...filtros, status: 'late' }}
                opcoes={opcoes}
                resumo={resumo({ total: 0 })}
            />,
        );
        expect(screen.getByText('Nenhuma submissão com esses filtros')).toBeTruthy();
    });
});
