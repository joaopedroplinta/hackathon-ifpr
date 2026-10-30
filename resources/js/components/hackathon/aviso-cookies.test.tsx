import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it } from 'vitest';

import AvisoCookies from './aviso-cookies';

const limpar = () => {
    document.cookie = 'cookie_consent=; max-age=0; path=/';
};

describe('AvisoCookies', () => {
    beforeEach(limpar);

    it('aparece quando não há resposta gravada', async () => {
        render(<AvisoCookies />);
        expect(await screen.findByRole('region', { name: 'Aviso de cookies' })).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Saiba quais' }).getAttribute('href')).toBe('/cookies/show');
    });

    it('grava "aceito" e some', async () => {
        render(<AvisoCookies />);
        await userEvent.click(await screen.findByRole('button', { name: 'Aceitar' }));

        expect(document.cookie).toContain('cookie_consent=aceito');
        expect(screen.queryByRole('region')).toBeNull();
    });

    it('grava "recusado" e some', async () => {
        render(<AvisoCookies />);
        await userEvent.click(await screen.findByRole('button', { name: 'Recusar' }));

        expect(document.cookie).toContain('cookie_consent=recusado');
        expect(screen.queryByRole('region')).toBeNull();
    });

    it('não aparece se já respondeu', () => {
        document.cookie = 'cookie_consent=aceito; path=/';
        render(<AvisoCookies />);
        expect(screen.queryByRole('region')).toBeNull();
    });
});
