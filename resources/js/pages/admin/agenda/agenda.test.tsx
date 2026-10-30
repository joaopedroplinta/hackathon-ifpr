import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';
import { type LinhaItemAgenda } from '@/types/agenda-admin';

import FormularioAgenda from './formulario';
import ListaAgenda from './index';

const item = (id: number, extra: Partial<LinhaItemAgenda> = {}): LinhaItemAgenda => ({
    id,
    titulo: `Atividade ${id}`,
    tipo_label: 'Palestra',
    inicia_em: '2026-05-10T15:00:00Z',
    termina_em: '2026-05-10T16:30:00Z',
    local: null,
    trilha: null,
    publicado: false,
    ...extra,
});

describe('ListaAgenda', () => {
    it('sem itens convida a criar o primeiro', () => {
        render(<ListaAgenda itens={[]} />);
        expect(screen.getByText('Nenhum item na agenda')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Novo item' }).getAttribute('href')).toBe('/painel/agenda/create');
    });

    it('mostra horário de Brasília, local, trilha e se está publicado', () => {
        render(<ListaAgenda itens={[item(1, { publicado: true, local: 'Auditório', trilha: { nome: 'IA', cor: '#00aa00' } }), item(2)]} />);

        expect(screen.getByText('2 itens', { exact: false })).toBeTruthy();
        expect(screen.getAllByText(/10\/05\/2026,? 12:00–13:30/)).toHaveLength(2);
        expect(screen.getByText('Auditório')).toBeTruthy();
        expect(screen.getByText('IA')).toBeTruthy();
        expect(screen.getByText('Publicado')).toBeTruthy();
        expect(screen.getByText('Rascunho')).toBeTruthy();
    });

    it('alterna publicação com o botão certo para cada estado', async () => {
        render(<ListaAgenda itens={[item(1, { publicado: true }), item(2)]} />);

        await userEvent.click(screen.getByRole('button', { name: 'Despublicar' }));
        await userEvent.click(screen.getByRole('button', { name: 'Publicar' }));

        expect(visits.map((v) => [v.method, v.url])).toEqual([
            ['patch', '/painel/agenda/publish/1'],
            ['patch', '/painel/agenda/publish/2'],
        ]);
    });

    it('edita pelo link e só remove depois de confirmar', async () => {
        render(<ListaAgenda itens={[item(5)]} />);

        expect(screen.getByRole('link', { name: 'Editar Atividade 5' }).getAttribute('href')).toBe('/painel/agenda/edit/5');

        await userEvent.click(screen.getByRole('button', { name: 'Remover Atividade 5' }));
        const dialogo = screen.getByRole('dialog', { name: 'Excluir item da agenda?' });
        expect(visits).toHaveLength(0);

        await userEvent.click(within(dialogo).getByRole('button', { name: 'Excluir item' }));
        expect(visits).toEqual([{ method: 'delete', url: '/painel/agenda/destroy/5', data: undefined }]);
    });

    it('cancelar a exclusão não envia nada', async () => {
        render(<ListaAgenda itens={[item(5)]} />);
        await userEvent.click(screen.getByRole('button', { name: 'Remover Atividade 5' }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(visits).toHaveLength(0);
    });
});

describe('FormularioAgenda', () => {
    const opcoes = {
        tipos: [{ valor: 'palestra' as const, rotulo: 'Palestra' }],
        trilhas: [{ id: 2, nome: 'IA' }],
    };

    it('novo item: envia com os horários convertidos de Brasília para UTC', async () => {
        render(<FormularioAgenda item={null} opcoes={opcoes} />);

        expect(screen.getByRole('heading', { level: 1, name: 'Novo item da agenda' })).toBeTruthy();
        await userEvent.type(screen.getByLabelText('Título'), 'Abertura');
        await userEvent.selectOptions(screen.getByLabelText('Tipo'), 'palestra');
        await userEvent.type(screen.getByLabelText('Início'), '2026-05-10T09:00');
        await userEvent.type(screen.getByLabelText('Término'), '2026-05-10T10:30');
        await userEvent.selectOptions(screen.getByLabelText('Trilha (opcional)'), '2');
        await userEvent.click(screen.getByRole('button', { name: 'Criar item' }));

        expect(visits).toHaveLength(1);
        expect(visits[0]).toMatchObject({
            method: 'post',
            url: '/painel/agenda/store',
            data: { title: 'Abertura', type: 'palestra', starts_at: '2026-05-10T12:00:00.000Z', ends_at: '2026-05-10T13:30:00.000Z', track_id: '2' },
        });
    });

    it('edição: preenche com o horário de Brasília e envia por PATCH', async () => {
        render(
            <FormularioAgenda
                opcoes={opcoes}
                item={{
                    id: 9,
                    title: 'Abertura',
                    description: null,
                    type: 'palestra',
                    starts_at: '2026-05-10T12:00:00Z',
                    ends_at: '2026-05-10T13:30:00Z',
                    location: 'Auditório',
                    speaker_name: null,
                    speaker_bio: null,
                    track_id: 2,
                }}
            />,
        );

        expect(screen.getByRole('heading', { level: 1, name: 'Editar item' })).toBeTruthy();
        expect((screen.getByLabelText('Início') as HTMLInputElement).value).toBe('2026-05-10T09:00');
        expect((screen.getByLabelText('Trilha (opcional)') as HTMLSelectElement).value).toBe('2');

        await userEvent.click(screen.getByRole('button', { name: 'Salvar alterações' }));
        expect(visits[0]).toMatchObject({ method: 'patch', url: '/painel/agenda/update/9', data: { starts_at: '2026-05-10T12:00:00.000Z' } });
    });

    it('mostra erro do título ligado ao campo', () => {
        formErrors.title = 'Informe o título.';
        render(<FormularioAgenda item={null} opcoes={opcoes} />);

        expect(screen.getAllByText('Informe o título.').length).toBeGreaterThan(0);
        expect(screen.getByLabelText('Título').getAttribute('aria-describedby')).toBe('title-erro');
    });
});
