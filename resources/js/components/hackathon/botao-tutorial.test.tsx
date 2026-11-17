import { page, setPage } from '@/test/inertia';
import { render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, it } from 'vitest';

import BotaoTutorial from '@/components/hackathon/botao-tutorial';

describe('BotaoTutorial', () => {
    beforeEach(() => setPage({ auth: { user: { tours_seen: ['dashboard'] } } }));

    it('não aparece em página sem tutorial', () => {
        page.component = 'settings/profile';
        render(<BotaoTutorial />);
        expect(screen.queryByRole('button', { name: 'Ver o tutorial desta tela' })).toBeNull();
    });

    it('aparece em página com tutorial, mesmo já visto', () => {
        page.component = 'dashboard';
        render(<BotaoTutorial />);
        expect(screen.getByRole('button', { name: 'Ver o tutorial desta tela' })).toBeTruthy();
    });
});
