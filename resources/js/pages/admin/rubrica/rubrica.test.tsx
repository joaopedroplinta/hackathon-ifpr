import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';

import ListaRubricas from './index';
import MostrarRubrica from './mostrar';

describe('ListaRubricas', () => {
    const rubricas = [
        { id: 1, nome: 'Rubrica 2026', ativa: true, total_criterios: 4, soma_pesos: 10 },
        { id: 2, nome: 'Rascunho', ativa: false, total_criterios: 1, soma_pesos: 2 },
    ];

    it('sem rubricas convida a criar a primeira', () => {
        render(<ListaRubricas rubricas={[]} />);
        expect(screen.getByText('Nenhuma rubrica cadastrada')).toBeTruthy();
    });

    it('cria uma rubrica nova pelo nome', async () => {
        render(<ListaRubricas rubricas={[]} />);
        await userEvent.type(screen.getByLabelText('Nova rubrica'), 'Rubrica 2027');
        await userEvent.click(screen.getByRole('button', { name: 'Criar' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/rubrica/store', data: { name: 'Rubrica 2027' } }]);
    });

    it('mostra situação, critérios e soma dos pesos', () => {
        render(<ListaRubricas rubricas={rubricas} />);

        expect(screen.getByText('Ativa')).toBeTruthy();
        expect(screen.getByText('Inativa')).toBeTruthy();
        expect(screen.getByText(/4 critérios · soma dos pesos: 10/)).toBeTruthy();
        expect(screen.getByText(/1 critério · soma dos pesos: 2/)).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Rubrica 2026' }).getAttribute('href')).toBe('/painel/rubrica/show/1');
    });

    it('só a rubrica inativa oferece "Ativar"', async () => {
        render(<ListaRubricas rubricas={rubricas} />);
        expect(screen.getAllByRole('button', { name: 'Ativar' })).toHaveLength(1);

        await userEvent.click(screen.getByRole('button', { name: 'Ativar' }));
        expect(visits).toEqual([{ method: 'patch', url: '/painel/rubrica/activate/2', data: {} }]);
    });

    it('exclui só depois de confirmar', async () => {
        render(<ListaRubricas rubricas={rubricas} />);
        await userEvent.click(screen.getByRole('button', { name: 'Remover Rascunho' }));
        const dialogo = screen.getByRole('dialog', { name: 'Excluir rubrica?' });
        expect(visits).toHaveLength(0);

        await userEvent.click(within(dialogo).getByRole('button', { name: 'Excluir rubrica' }));
        expect(visits).toEqual([{ method: 'delete', url: '/painel/rubrica/destroy/2', data: undefined }]);
    });

    it('cancelar a exclusão não envia nada', async () => {
        render(<ListaRubricas rubricas={rubricas} />);
        await userEvent.click(screen.getByRole('button', { name: 'Remover Rascunho' }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(visits).toHaveLength(0);
    });

    it('mostra erro de validação do nome', () => {
        formErrors.name = 'Informe o nome.';
        render(<ListaRubricas rubricas={[]} />);
        expect(screen.getAllByText('Informe o nome.').length).toBeGreaterThan(0);
    });
});

describe('MostrarRubrica', () => {
    const rubrica = { id: 1, nome: 'Rubrica 2026', ativa: true };
    const criterios = [
        { id: 10, nome: 'Inovação', descricao: 'Originalidade', peso: 3, nota_maxima: 10 },
        { id: 11, nome: 'Execução', descricao: null, peso: 1, nota_maxima: 5 },
    ];

    it('mostra a soma dos pesos e os critérios', () => {
        render(<MostrarRubrica rubrica={rubrica} criterios={criterios} />);

        expect(screen.getByRole('heading', { level: 1, name: 'Rubrica 2026' })).toBeTruthy();
        expect(screen.getByText(/Soma dos pesos: 4\./)).toBeTruthy();
        expect(screen.getByText('peso 3 · nota até 10')).toBeTruthy();
        expect(screen.getByText('Originalidade')).toBeTruthy();
        expect(screen.getByText('Ativa')).toBeTruthy();
    });

    it('sem critérios orienta adicionar o primeiro', () => {
        render(<MostrarRubrica rubrica={{ ...rubrica, ativa: false }} criterios={[]} />);

        expect(screen.getByText('Nenhum critério ainda. Adicione o primeiro abaixo.')).toBeTruthy();
        expect(screen.getByText('Inativa')).toBeTruthy();
    });

    it('adiciona um critério novo', async () => {
        render(<MostrarRubrica rubrica={rubrica} criterios={[]} />);

        await userEvent.type(screen.getByLabelText('Nome'), 'Impacto');
        await userEvent.type(screen.getByLabelText('Peso'), '2');
        await userEvent.type(screen.getByLabelText('Nota máxima'), '10');
        await userEvent.click(screen.getByRole('button', { name: 'Adicionar critério' }));

        expect(visits).toEqual([
            { method: 'post', url: '/painel/rubrica/criteria/store/1', data: { name: 'Impacto', description: '', weight: '2', max_score: '10' } },
        ]);
    });

    it('edita um critério já existente com os valores atuais', async () => {
        render(<MostrarRubrica rubrica={rubrica} criterios={criterios} />);

        await userEvent.click(screen.getByRole('button', { name: 'Editar Inovação' }));
        expect((screen.getByLabelText('Peso', { selector: '#edit-weight-10' }) as HTMLInputElement).value).toBe('3');

        const nome = screen.getByLabelText('Nome', { selector: '#edit-name-10' });
        await userEvent.clear(nome);
        await userEvent.type(nome, 'Inovação técnica');
        await userEvent.click(screen.getByRole('button', { name: 'Salvar' }));

        expect(visits).toEqual([
            {
                method: 'patch',
                url: '/painel/rubrica/criteria/update/10',
                data: { name: 'Inovação técnica', description: 'Originalidade', weight: '3', max_score: '10' },
            },
        ]);
        expect(screen.queryByRole('button', { name: 'Salvar' })).toBeNull();
    });

    it('cancelar a edição volta à lista sem enviar', async () => {
        render(<MostrarRubrica rubrica={rubrica} criterios={criterios} />);
        await userEvent.click(screen.getByRole('button', { name: 'Editar Inovação' }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(visits).toHaveLength(0);
        expect(screen.getByText('peso 3 · nota até 10')).toBeTruthy();
    });

    it('remove um critério só depois de confirmar', async () => {
        render(<MostrarRubrica rubrica={rubrica} criterios={criterios} />);
        await userEvent.click(screen.getByRole('button', { name: 'Remover Execução' }));
        const dialogo = screen.getByRole('dialog', { name: 'Excluir critério?' });
        expect(visits).toHaveLength(0);

        await userEvent.click(within(dialogo).getByRole('button', { name: 'Excluir critério' }));
        expect(visits).toEqual([{ method: 'delete', url: '/painel/rubrica/criteria/destroy/11', data: undefined }]);
    });
});
