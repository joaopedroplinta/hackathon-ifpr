import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ContadorEvento from './contador-evento';

describe('ContadorEvento', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-01-01T00:00:00Z'));
    });
    afterEach(() => vi.useRealTimers());

    it('decompõe o tempo restante em dias, horas, minutos e segundos', () => {
        render(<ContadorEvento alvo="2026-01-02T03:04:05Z" rotulo="Começa em" />);

        const timer = screen.getByRole('timer', { name: 'Começa em' });
        expect(timer.textContent).toContain('01dias');
        expect(timer.textContent).toContain('03horas');
        expect(timer.textContent).toContain('04min');
        expect(timer.textContent).toContain('05seg');
    });

    it('atualiza a cada segundo', () => {
        render(<ContadorEvento alvo="2026-01-01T00:00:10Z" rotulo="Começa em" />);
        act(() => vi.advanceTimersByTime(3000));
        expect(screen.getByRole('timer').textContent).toContain('07seg');
    });

    it('some quando o prazo passa', () => {
        render(<ContadorEvento alvo="2026-01-01T00:00:02Z" rotulo="Começa em" />);
        act(() => vi.advanceTimersByTime(3000));
        expect(screen.queryByRole('timer')).toBeNull();
    });
});
