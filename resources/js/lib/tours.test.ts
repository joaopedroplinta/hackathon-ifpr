import { describe, expect, it } from 'vitest';

import { tours } from '@/lib/tours';

describe('catálogo de tutoriais', () => {
    const lista = Object.entries(tours);

    it('usa ids no formato aceito pelo servidor e sem repetição', () => {
        const ids = lista.map(([, t]) => t.id);
        ids.forEach((id) => expect(id).toMatch(/^[a-z0-9.-]{1,64}$/));
        expect(new Set(ids).size).toBe(ids.length);
    });

    it('todo passo tem alvo, título e descrição', () => {
        for (const [, tour] of lista) {
            expect(tour.steps.length).toBeGreaterThan(0);
            tour.steps.forEach((p) => {
                expect(p.target).not.toBe('');
                expect(p.title).not.toBe('');
                expect(p.description).not.toBe('');
            });
        }
    });
});

describe('tutoriais manuais', () => {
    it('telas de uso crítico no evento não abrem sozinhas', () => {
        expect(tours['admin/checkin/index'].manual).toBe(true);
        expect(tours['jurado/avaliar'].manual).toBe(true);
    });
});
