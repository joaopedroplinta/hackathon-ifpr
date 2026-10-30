import { render } from '@testing-library/react';
import { toast } from 'sonner';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { setPage } from '@/test/inertia';

import FlashMessages from './flash-messages';

vi.mock('sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() }, Toaster: () => null }));

describe('FlashMessages', () => {
    beforeEach(() => vi.clearAllMocks());

    it('dispara toast de sucesso', () => {
        setPage({ flash: { sucesso: 'Salvo.' } });
        render(<FlashMessages />);
        expect(toast.success).toHaveBeenCalledWith('Salvo.');
        expect(toast.error).not.toHaveBeenCalled();
    });

    it('dispara toast de erro', () => {
        setPage({ flash: { erro: 'Falhou.' } });
        render(<FlashMessages />);
        expect(toast.error).toHaveBeenCalledWith('Falhou.');
    });

    it('não dispara nada sem flash', () => {
        render(<FlashMessages />);
        expect(toast.success).not.toHaveBeenCalled();
        expect(toast.error).not.toHaveBeenCalled();
    });
});
