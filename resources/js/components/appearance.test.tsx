import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it } from 'vitest';

import AppearanceToggleTab from './appearance-tabs';

describe('AppearanceToggleTab', () => {
    beforeEach(() => localStorage.clear());

    it('troca o tema ao clicar e marca a opção escolhida', async () => {
        render(<AppearanceToggleTab />);

        await userEvent.click(screen.getByRole('radio', { name: 'Escuro' }));

        expect(screen.getByRole('radio', { name: 'Escuro' }).getAttribute('aria-checked')).toBe('true');
        expect(screen.getByRole('radio', { name: 'Claro' }).getAttribute('aria-checked')).toBe('false');
        expect(localStorage.getItem('appearance')).toBe('dark');
    });
});
