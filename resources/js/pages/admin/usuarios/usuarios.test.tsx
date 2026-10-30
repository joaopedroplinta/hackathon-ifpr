import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { visits } from '@/test/inertia';
import { type Paginated } from '@/types';
import { type LinhaUsuario } from '@/types/usuarios';

import ListaUsuarios from './index';

const opcoes = [
    { value: 'judge', label: 'Jurado' },
    { value: 'organizer', label: 'Organizador' },
    { value: 'admin', label: 'Administrador' },
];
const usuario = (id: number, extra: Partial<LinhaUsuario> = {}): LinhaUsuario => ({
    id,
    nome: `Pessoa ${id}`,
    email: `p${id}@ifpr.edu.br`,
    papeis: [],
    sou_eu: false,
    ...extra,
});
const pagina = (data: LinhaUsuario[], extra: Partial<Paginated<LinhaUsuario>> = {}): Paginated<LinhaUsuario> => ({
    data,
    links: [
        { url: '/u?page=1', label: '1', active: true },
        { url: '/u?page=2', label: '2', active: false },
    ],
    current_page: 1,
    last_page: 1,
    from: 1,
    to: data.length,
    total: data.length,
    ...extra,
});
const montar = (usuarios: Paginated<LinhaUsuario>, busca: string | null = null) =>
    render(<ListaUsuarios usuarios={usuarios} filtros={{ busca }} opcoes_papeis={opcoes} />);

describe('ListaUsuarios', () => {
    it('sem resultado explica e sugere outra busca', () => {
        montar(pagina([]));
        expect(screen.getByText('Nenhum usuário encontrado')).toBeTruthy();
    });

    it('sem papel privilegiado aparece como participante; com papel, mostra o rótulo em português', () => {
        montar(pagina([usuario(1), usuario(2, { papeis: ['judge', 'organizer'] })]));

        expect(screen.getByText('Participante')).toBeTruthy();
        expect(screen.getByText('Jurado')).toBeTruthy();
        expect(screen.getByText('Organizador')).toBeTruthy();
    });

    it('marca quem é a própria pessoa logada', () => {
        montar(pagina([usuario(1, { sou_eu: true })]));
        expect(screen.getByText('(você)')).toBeTruthy();
    });

    it('busca por nome ou e-mail; sem termo manda consulta vazia', async () => {
        montar(pagina([usuario(1)]));
        await userEvent.click(screen.getByRole('button', { name: 'Buscar' }));
        await userEvent.type(screen.getByLabelText('Buscar por nome ou e-mail'), 'ana');
        await userEvent.click(screen.getByRole('button', { name: 'Buscar' }));

        expect(visits.map((v) => v.data)).toEqual([{}, { busca: 'ana' }]);
    });

    it('limpar a busca volta à lista completa', async () => {
        montar(pagina([usuario(1)]), 'ana');
        await userEvent.click(screen.getByRole('button', { name: 'Limpar' }));

        expect(visits).toEqual([{ method: 'get', url: '/admin/usuarios/index', data: undefined }]);
    });

    it('edita papéis e envia a lista marcada', async () => {
        montar(pagina([usuario(5, { papeis: ['judge'] })]));
        await userEvent.click(screen.getByRole('button', { name: 'Editar papéis' }));
        const dialogo = screen.getByRole('dialog', { name: 'Papéis de Pessoa 5' });

        expect(within(dialogo).getByRole('checkbox', { name: 'Jurado' }).getAttribute('aria-checked')).toBe('true');
        await userEvent.click(within(dialogo).getByRole('checkbox', { name: 'Organizador' }));
        await userEvent.click(within(dialogo).getByRole('checkbox', { name: 'Jurado' }));
        await userEvent.click(within(dialogo).getByRole('button', { name: 'Salvar' }));

        expect(visits).toEqual([{ method: 'patch', url: '/admin/usuarios/update/5', data: { roles: ['organizer'] } }]);
    });

    it('admin não consegue tirar o próprio papel de admin', async () => {
        montar(pagina([usuario(5, { papeis: ['admin'], sou_eu: true })]));
        await userEvent.click(screen.getByRole('button', { name: 'Editar papéis' }));

        const admin = screen.getByRole('checkbox', { name: /Administrador/ });
        expect(admin.hasAttribute('disabled')).toBe(true);
        expect(screen.getByText('(você não pode remover de si mesmo)')).toBeTruthy();
    });

    it('fechar sem salvar descarta as marcações', async () => {
        montar(pagina([usuario(5)]));
        await userEvent.click(screen.getByRole('button', { name: 'Editar papéis' }));
        await userEvent.click(screen.getByRole('checkbox', { name: 'Jurado' }));
        await userEvent.keyboard('{Escape}');

        expect(visits).toHaveLength(0);
        await userEvent.click(screen.getByRole('button', { name: 'Editar papéis' }));
        expect(screen.getByRole('checkbox', { name: 'Jurado' }).getAttribute('aria-checked')).toBe('false');
    });

    it('pagina só com mais de uma página', () => {
        const { unmount } = montar(pagina([usuario(1)]));
        expect(screen.queryByRole('navigation', { name: 'Paginação' })).toBeNull();
        unmount();

        montar(pagina([usuario(1)], { last_page: 2 }));
        expect(screen.getByRole('link', { name: '2' }).getAttribute('href')).toBe('/u?page=2');
    });
});
