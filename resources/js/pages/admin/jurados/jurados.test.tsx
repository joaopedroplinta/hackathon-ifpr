import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { visits } from '@/test/inertia';

import JuradosIndex from './index';

const submissao = (jurados: Parameters<typeof JuradosIndex>[0]['submissoes'][number]['jurados'] = []) => ({
    id: 10,
    titulo: 'App de Rotas',
    equipe: 'Os Devs',
    jurados,
});
const props = {
    submissoes: [submissao()],
    jurados: [{ id: 3, nome: 'Dra. Silva', total_atribuicoes: 1 }],
    conflitos: [],
    jurados_por_submissao: 2,
    opcoes: { equipes: [{ id: 5, nome: 'Os Devs' }] },
};
const montar = (extra: Partial<typeof props> = {}) => render(<JuradosIndex {...props} {...extra} />);

describe('JuradosIndex', () => {
    it('sem jurados nem submissões explica cada vazio', () => {
        montar({ jurados: [], submissoes: [] });
        expect(screen.getByText('Nenhum usuário com o papel de jurado ainda.')).toBeTruthy();
        expect(screen.getByText('Nenhuma submissão enviada ainda.')).toBeTruthy();
    });

    it('mostra a carga de cada jurado no singular e no plural', () => {
        montar({
            jurados: [
                { id: 3, nome: 'Dra. Silva', total_atribuicoes: 1 },
                { id: 4, nome: 'Dr. Souza', total_atribuicoes: 4 },
            ],
        });
        expect(screen.getByText(/Dra. Silva · 1 submissão$/)).toBeTruthy();
        expect(screen.getByText(/Dr. Souza · 4 submissões/)).toBeTruthy();
    });

    it('distribui automaticamente', async () => {
        montar();
        await userEvent.click(screen.getByRole('button', { name: /Distribuir automaticamente/ }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/jurados/distribute', data: {} }]);
    });

    it('salva quantos jurados por submissão', async () => {
        montar();
        const campo = screen.getByLabelText('Jurados por submissão');
        expect((campo as HTMLInputElement).value).toBe('2');

        await userEvent.clear(campo);
        await userEvent.type(campo, '3');
        await userEvent.click(screen.getByRole('button', { name: 'Salvar' }));

        expect(visits).toEqual([{ method: 'patch', url: '/painel/jurados/config', data: { judges_per_submission: '3' } }]);
    });

    it('atribui manualmente um jurado a uma submissão', async () => {
        montar();
        await userEvent.selectOptions(screen.getByLabelText('Submissão'), '10');
        await userEvent.selectOptions(screen.getAllByLabelText('Jurado')[0], '3');
        await userEvent.click(screen.getByRole('button', { name: 'Atribuir' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/jurados/store', data: { judge_id: '3', submission_id: '10' } }]);
    });

    it('submissão sem jurado avisa', () => {
        montar();
        expect(screen.getByText('Nenhum jurado atribuído ainda.')).toBeTruthy();
    });

    const emAndamento = () =>
        montar({
            submissoes: [submissao([{ atribuicao_id: 21, jurado_id: 3, nome: 'Dra. Silva', status_label: 'Pendente', avaliacao_enviada: false }])],
        });

    it('jurado com avaliação em andamento pode ser reatribuído', async () => {
        emAndamento();

        expect(screen.getByText('· Pendente')).toBeTruthy();
        expect(screen.queryByRole('button', { name: /Reabrir avaliação/ })).toBeNull();

        await userEvent.click(screen.getByRole('button', { name: 'Reatribuir vaga de Dra. Silva' }));
        expect(visits).toEqual([{ method: 'post', url: '/painel/jurados/reassign/21', data: {} }]);
        // Enquanto a ação não termina, os botões travam: clique duplo não repete a ação.
        expect((screen.getByRole('button', { name: 'Reatribuir vaga de Dra. Silva' }) as HTMLButtonElement).disabled).toBe(true);
    });

    it('jurado com avaliação em andamento pode ser removido', async () => {
        emAndamento();
        await userEvent.click(screen.getByRole('button', { name: 'Remover Dra. Silva desta submissão' }));

        expect(visits).toEqual([{ method: 'delete', url: '/painel/jurados/destroy/21', data: undefined }]);
    });

    it('avaliação enviada não é apagada: só reabre, com motivo', async () => {
        montar({
            submissoes: [submissao([{ atribuicao_id: 21, jurado_id: 3, nome: 'Dra. Silva', status_label: 'Enviada', avaliacao_enviada: true }])],
        });

        expect(screen.queryByRole('button', { name: /Remover Dra. Silva/ })).toBeNull();
        expect(screen.queryByRole('button', { name: /Reatribuir/ })).toBeNull();

        await userEvent.click(screen.getByRole('button', { name: 'Reabrir avaliação de Dra. Silva para correção' }));
        await userEvent.type(screen.getByLabelText('Motivo da correção na avaliação de Dra. Silva'), 'Revisar impacto');
        await userEvent.click(screen.getByRole('button', { name: 'Confirmar reabertura' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/jurados/reopen-evaluation/21', data: { reason: 'Revisar impacto' } }]);
    });

    it('cancelar a reabertura fecha o formulário sem enviar', async () => {
        montar({
            submissoes: [submissao([{ atribuicao_id: 21, jurado_id: 3, nome: 'Dra. Silva', status_label: 'Enviada', avaliacao_enviada: true }])],
        });
        await userEvent.click(screen.getByRole('button', { name: /Reabrir avaliação/ }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(screen.queryByLabelText(/Motivo da correção/)).toBeNull();
        expect(visits).toHaveLength(0);
    });

    it('registra um conflito de interesse', async () => {
        montar();
        await userEvent.selectOptions(screen.getAllByLabelText('Jurado')[1], '3');
        await userEvent.selectOptions(screen.getAllByLabelText('Equipe')[0], '5');
        await userEvent.type(screen.getByLabelText('Motivo (opcional)'), 'Orientador');
        await userEvent.click(screen.getByRole('button', { name: 'Registrar' }));

        expect(visits).toEqual([
            { method: 'post', url: '/painel/jurados/conflicts/store', data: { judge_id: '3', team_id: '5', reason: 'Orientador' } },
        ]);
    });

    it('lista conflitos com motivo e permite remover', async () => {
        montar({ conflitos: [{ id: 8, jurado: 'Dra. Silva', equipe: 'Os Devs', motivo: 'Orientadora' }] });

        expect(screen.getByText(/Dra. Silva × Os Devs/)).toBeTruthy();
        expect(screen.getByText('— Orientadora')).toBeTruthy();

        await userEvent.click(screen.getByRole('button', { name: 'Remover conflito entre Dra. Silva e Os Devs' }));
        expect(visits).toEqual([{ method: 'delete', url: '/painel/jurados/conflicts/destroy/8', data: undefined }]);
    });
});
