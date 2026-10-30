import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { visits } from '@/test/inertia';

import ResultadosIndex from './index';

const semPendencia = { atrasados_sem_decisao: [], submissoes_sem_nota: [], jurados_incompletos: [], empates: [] };
const montar = (extra: Partial<Parameters<typeof ResultadosIndex>[0]> = {}) =>
    render(<ResultadosIndex resultados={[]} pendencias={semPendencia} publicado_em={null} computado_em={null} {...extra} />);

describe('ResultadosIndex (organização)', () => {
    it('antes de calcular: estado vazio e "Ainda não publicado"', () => {
        montar();
        expect(screen.getByText('Ainda não publicado.')).toBeTruthy();
        expect(screen.getByText('Nenhum resultado calculado ainda.')).toBeTruthy();
    });

    it('mostra quando foi publicado e calculado', () => {
        montar({ publicado_em: '10/05 18:00', computado_em: '10/05 17:00' });
        expect(screen.getByText(/Publicado em 10\/05 18:00\. Último cálculo: 10\/05 17:00\./)).toBeTruthy();
    });

    it('lista o ranking com traço quando falta posição e "Sem nota" quando falta nota', () => {
        montar({
            resultados: [
                { submission_id: 1, titulo: 'Primeiro', equipe: 'A', trilha: 'IA', nota_final: 9.5, rank_overall: 1, rank_track: 1 },
                { submission_id: 2, titulo: 'Sem nada', equipe: 'B', trilha: null, nota_final: null, rank_overall: null, rank_track: null },
            ],
        });

        expect(screen.getByText('Primeiro')).toBeTruthy();
        expect(screen.getByText('9.5')).toBeTruthy();
        expect(screen.getByText('(IA)')).toBeTruthy();
        expect(screen.getByText('Sem nota')).toBeTruthy();
        expect(screen.getAllByText('—').length).toBe(2);
    });

    it('recalcular dispara a rota própria', async () => {
        montar();
        await userEvent.click(screen.getByRole('button', { name: 'Recalcular' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/resultados/recompute', data: {} }]);
    });

    it('sem pendências, publica direto e sem pedir confirmação', async () => {
        montar();
        await userEvent.click(screen.getByRole('button', { name: 'Publicar resultado' }));

        expect(screen.queryByRole('dialog')).toBeNull();
        expect(visits).toEqual([{ method: 'post', url: '/painel/resultados/publish', data: { confirmar_pendencias: false } }]);
    });

    it('com pendências, mostra o que falta e exige confirmar antes de publicar', async () => {
        montar({
            pendencias: {
                atrasados_sem_decisao: ['Projeto Atrasado'],
                submissoes_sem_nota: ['Projeto Sem Nota'],
                jurados_incompletos: [{ titulo: 'Projeto X', enviadas: 1, total: 3 }],
                empates: [{ posicao: 2, submissoes: ['A', 'B'] }],
            },
        });

        expect(screen.getByText('Envios fora do prazo sem decisão')).toBeTruthy();
        expect(screen.getByText('Projeto Atrasado')).toBeTruthy();
        expect(screen.getByText('Projeto Sem Nota')).toBeTruthy();
        expect(screen.getByText('Projeto X — 1 de 3 avaliações enviadas')).toBeTruthy();
        expect(screen.getByText('2º lugar: A, B')).toBeTruthy();

        await userEvent.click(screen.getByRole('button', { name: 'Publicar resultado' }));
        const dialogo = screen.getByRole('dialog', { name: 'Publicar resultados com pendências?' });
        expect(visits).toHaveLength(0);

        await userEvent.click(within(dialogo).getByRole('button', { name: 'Publicar mesmo assim' }));
        expect(visits).toEqual([{ method: 'post', url: '/painel/resultados/publish', data: { confirmar_pendencias: true } }]);
    });

    it('"Voltar e revisar" não publica', async () => {
        montar({ pendencias: { ...semPendencia, empates: [{ posicao: 1, submissoes: ['A', 'B'] }] } });
        await userEvent.click(screen.getByRole('button', { name: 'Publicar resultado' }));
        await userEvent.click(screen.getByRole('button', { name: 'Voltar e revisar' }));

        expect(visits).toHaveLength(0);
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('só mostra os blocos de pendência que têm itens', () => {
        montar({ pendencias: { ...semPendencia, empates: [{ posicao: 1, submissoes: ['A', 'B'] }] } });

        expect(screen.getByText('Empates pendentes')).toBeTruthy();
        expect(screen.queryByText('Submissões sem nota')).toBeNull();
        expect(screen.queryByText('Jurados incompletos')).toBeNull();
    });
});
