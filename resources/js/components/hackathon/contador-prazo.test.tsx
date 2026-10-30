import { render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ContadorPrazo from './contador-prazo';

const AGORA = new Date('2026-10-30T12:00:00Z');
const depois = (ms: number) => new Date(AGORA.getTime() + ms).toISOString();
const HORA = 3600 * 1000;

function renderizar(encerraEm: string | null, envioAindaAceito = true) {
    render(<ContadorPrazo prazo={{ encerra_em: encerraEm, aberto: true }} envioAindaAceito={envioAindaAceito} />);

    return screen.getByRole('status', { hidden: true }).textContent ?? '';
}

describe('ContadorPrazo', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(AGORA);
    });

    afterEach(() => vi.useRealTimers());

    it('avisa quando não há prazo definido', () => {
        render(<ContadorPrazo prazo={{ encerra_em: null, aberto: true }} envioAindaAceito />);
        expect(screen.getByText('Sem prazo definido para o envio.')).toBeTruthy();
    });

    it('mostra dias e horas quando falta mais de um dia', () => {
        expect(renderizar(depois(2 * 24 * HORA + 3 * HORA))).toContain('Faltam 2d 3h para o prazo');
    });

    it('mostra horas e minutos quando falta menos de um dia', () => {
        expect(renderizar(depois(5 * HORA + 30 * 60 * 1000))).toContain('Faltam 5h 30min para o prazo');
    });

    it('mostra minutos e segundos na última hora', () => {
        expect(renderizar(depois(90 * 1000))).toContain('Faltam 1min 30s para o prazo');
    });

    it('usa o singular quando falta menos de 2 segundos', () => {
        expect(renderizar(depois(1500))).toContain('Falta 0min 1s para o prazo');
    });

    it('usa o plural a partir de 2 segundos', () => {
        expect(renderizar(depois(5000))).toContain('Faltam 0min 5s para o prazo');
    });

    it('depois do prazo, diz que o envio ainda é aceito só se a equipe ainda pode enviar', () => {
        expect(renderizar(depois(-HORA), true)).toContain('O envio ainda é aceito, mas entra como fora do prazo.');
    });

    it('depois do prazo, não promete aceitar o envio de equipe que já entregou', () => {
        const texto = renderizar(depois(-HORA), false);

        expect(texto).toContain('Prazo encerrado em');
        expect(texto).not.toContain('ainda é aceito');
    });
});
