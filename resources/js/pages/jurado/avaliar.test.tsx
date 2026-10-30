import { act, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { formErrors, visits } from '@/test/inertia';

import AvaliarSubmissao from './avaliar';

const criterios = [
    { id: 1, nome: 'Inovação', descricao: 'Originalidade', peso: 3, nota_maxima: 10 },
    { id: 2, nome: 'Execução', descricao: null, peso: 1, nota_maxima: 5 },
];
const submissao = {
    id: 9,
    titulo: 'App de Rotas',
    equipe: 'Equipe Alfa',
    resumo: 'Planeja trajetos',
    descricao: null,
    repo_url: 'https://github.com/eq/app',
    video_url: null,
    deploy_url: null,
};
const vazia = { overall_comment: null, notas: [] };

const montar = (props: Partial<Parameters<typeof AvaliarSubmissao>[0]> = {}) =>
    render(<AvaliarSubmissao submissao={submissao} criterios={criterios} avaliacao={vazia} somente_leitura={false} {...props} />);

const nota = (criterio: string) => screen.getByLabelText(criterio) as HTMLInputElement;

describe('AvaliarSubmissao', () => {
    beforeEach(() => vi.useFakeTimers());
    afterEach(() => vi.useRealTimers());

    it('mostra o projeto e links só dos campos preenchidos', () => {
        montar();

        expect(screen.getByRole('heading', { name: 'App de Rotas' })).toBeTruthy();
        expect(screen.getByText('Equipe Alfa')).toBeTruthy();
        // O rodapé da sidebar também tem um "Repositório" (do projeto); este é o da equipe.
        const repo = screen.getAllByRole('link', { name: 'Repositório' }).find((l) => l.getAttribute('href') === submissao.repo_url);
        expect(repo?.getAttribute('rel')).toContain('noopener');
        expect(screen.queryByRole('link', { name: 'Vídeo de demonstração' })).toBeNull();
        expect(screen.queryByRole('link', { name: 'Aplicação publicada' })).toBeNull();
    });

    it('envio fica bloqueado até todos os critérios terem nota', () => {
        montar();
        const enviar = screen.getByRole('button', { name: 'Enviar avaliação' }) as HTMLButtonElement;

        expect(enviar.disabled).toBe(true);
        expect(screen.getByText('Preencha uma nota em todos os critérios para liberar o envio.')).toBeTruthy();

        fireEvent.change(nota('Inovação'), { target: { value: '8' } });
        expect(enviar.disabled).toBe(true);
        expect(screen.getByRole('progressbar', { name: 'Critérios avaliados' }).getAttribute('aria-valuenow')).toBe('1');

        fireEvent.change(nota('Execução'), { target: { value: '4.5' } });
        expect(enviar.disabled).toBe(false);
        expect(screen.getByText('Todos os critérios foram preenchidos. Você já pode enviar.')).toBeTruthy();
    });

    it('nota zero é válida (diferente de vazio)', () => {
        montar({
            avaliacao: {
                overall_comment: null,
                notas: [
                    { criterion_id: 1, score: 0, comment: null },
                    { criterion_id: 2, score: 0, comment: null },
                ],
            },
        });
        expect((screen.getByRole('button', { name: 'Enviar avaliação' }) as HTMLButtonElement).disabled).toBe(false);
    });

    it('carrega notas e comentários já salvos', () => {
        montar({
            avaliacao: { overall_comment: 'Bom', notas: [{ criterion_id: 1, score: 7, comment: 'Criativo' }] },
        });

        expect(nota('Inovação').value).toBe('7');
        expect((screen.getByLabelText('Comentário sobre Inovação') as HTMLTextAreaElement).value).toBe('Criativo');
        expect((screen.getByLabelText('Comentário geral (opcional)') as HTMLTextAreaElement).value).toBe('Bom');
    });

    it('salva rascunho sozinho 800ms depois de mudar, e não na montagem', () => {
        montar();
        act(() => vi.advanceTimersByTime(2000));
        expect(visits).toHaveLength(0);

        fireEvent.change(nota('Inovação'), { target: { value: '8' } });
        act(() => vi.advanceTimersByTime(700));
        expect(visits).toHaveLength(0);

        act(() => vi.advanceTimersByTime(200));
        expect(visits).toHaveLength(1);
        expect(visits[0]).toMatchObject({ method: 'post', url: '/jurado/avaliar/autosave/9' });
    });

    it('várias mudanças seguidas viram um único salvamento', () => {
        montar();
        fireEvent.change(nota('Inovação'), { target: { value: '1' } });
        act(() => vi.advanceTimersByTime(400));
        fireEvent.change(nota('Inovação'), { target: { value: '2' } });
        act(() => vi.advanceTimersByTime(400));
        fireEvent.change(nota('Inovação'), { target: { value: '3' } });
        act(() => vi.advanceTimersByTime(1000));

        expect(visits).toHaveLength(1);
    });

    it('enviar cancela o rascunho pendente e manda a avaliação', () => {
        montar({
            avaliacao: {
                overall_comment: null,
                notas: [
                    { criterion_id: 1, score: 8, comment: null },
                    { criterion_id: 2, score: 4, comment: null },
                ],
            },
        });
        fireEvent.change(screen.getByLabelText('Comentário geral (opcional)'), { target: { value: 'Ótimo' } });

        fireEvent.click(screen.getByRole('button', { name: 'Enviar avaliação' }));
        act(() => vi.advanceTimersByTime(2000));

        expect(visits.map((v) => v.url)).toEqual(['/jurado/avaliar/enviar/9']);
    });

    it('avaliação enviada vira somente leitura, sem botão nem rascunho automático', () => {
        montar({ somente_leitura: true, avaliacao: { overall_comment: null, notas: [{ criterion_id: 1, score: 8, comment: null }] } });

        expect(screen.getByText(/Avaliação já enviada/)).toBeTruthy();
        expect(nota('Inovação').disabled).toBe(true);
        expect(screen.queryByRole('button', { name: 'Enviar avaliação' })).toBeNull();

        act(() => vi.advanceTimersByTime(2000));
        expect(visits).toHaveLength(0);
    });

    it('sem rubrica ativa avisa o jurado e não deixa enviar', () => {
        montar({ criterios: [] });

        expect(screen.getByText(/Nenhuma rubrica ativa/)).toBeTruthy();
        expect((screen.getByRole('button', { name: 'Enviar avaliação' }) as HTMLButtonElement).disabled).toBe(true);
        expect(screen.getByText(/Sem critérios definidos/)).toBeTruthy();
        expect(screen.queryByRole('progressbar', { name: 'Critérios avaliados' })).toBeNull();
    });

    it('mostra o erro de nota acima do máximo ligado ao campo certo', () => {
        formErrors['scores.1.score'] = 'A nota máxima de Execução é 5.';
        montar();

        expect(screen.getAllByText('A nota máxima de Execução é 5.').length).toBeGreaterThan(0);
        expect(nota('Execução').getAttribute('aria-describedby')).toBe('score-2-erro');
        expect(nota('Inovação').getAttribute('aria-describedby')).toBeNull();
    });
});
