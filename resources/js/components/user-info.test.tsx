import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { type User } from '@/types';

import { UserInfo } from './user-info';

const user: User = {
    id: 1,
    name: 'Maria Souza',
    email: 'maria@ifpr.edu.br',
    email_verified_at: null,
    created_at: '',
    updated_at: '',
};

describe('UserInfo', () => {
    it('mostra nome e iniciais de fallback', () => {
        render(<UserInfo user={user} />);
        expect(screen.getByText('Maria Souza')).toBeTruthy();
        expect(screen.getByText('MS')).toBeTruthy();
        expect(screen.queryByText('maria@ifpr.edu.br')).toBeNull();
    });

    it('mostra o e-mail quando pedido', () => {
        render(<UserInfo user={user} showEmail />);
        expect(screen.getByText('maria@ifpr.edu.br')).toBeTruthy();
    });
});
