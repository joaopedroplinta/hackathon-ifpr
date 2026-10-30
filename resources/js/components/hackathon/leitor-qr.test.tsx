import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import LeitorQr from './leitor-qr';

const scanner = vi.hoisted(() => ({
    instancias: [] as { cb: (r: { data: string }) => void; start: () => Promise<void>; stop: () => void; destroy: () => void }[],
}));

vi.mock('qr-scanner', () => ({
    default: class {
        start = vi.fn().mockResolvedValue(undefined);
        stop = vi.fn();
        destroy = vi.fn();
        cb: (r: { data: string }) => void;
        constructor(_video: unknown, cb: (r: { data: string }) => void) {
            this.cb = cb;
            scanner.instancias.push(this);
        }
    },
}));

const camera = (getUserMedia: () => Promise<unknown>) =>
    Object.defineProperty(navigator, 'mediaDevices', { configurable: true, value: { getUserMedia } });

describe('LeitorQr', () => {
    beforeEach(() => {
        scanner.instancias.length = 0;
        Object.defineProperty(window, 'isSecureContext', { configurable: true, value: true });
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });
    afterEach(() => vi.restoreAllMocks());

    it('fora de HTTPS avisa e não oferece a câmera', () => {
        Object.defineProperty(window, 'isSecureContext', { configurable: true, value: false });
        render(<LeitorQr onDecode={() => {}} />);

        expect(screen.getByText(/só funciona em conexão segura/)).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Abrir câmera' })).toBeNull();
    });

    it('só abre a câmera depois do toque e entrega o QR lido', async () => {
        const parar = vi.fn();
        camera(() => Promise.resolve({ getTracks: () => [{ stop: parar }] }));
        const onDecode = vi.fn();
        render(<LeitorQr onDecode={onDecode} />);

        await userEvent.click(screen.getByRole('button', { name: 'Abrir câmera' }));
        await waitFor(() => expect(scanner.instancias).toHaveLength(1));
        expect(parar).toHaveBeenCalled();

        scanner.instancias[0].cb({ data: 'token-123' });
        expect(onDecode).toHaveBeenCalledWith('token-123');
    });

    it('permissão negada mostra a orientação e o detalhe técnico', async () => {
        camera(() => Promise.reject(Object.assign(new Error('x'), { name: 'NotAllowedError' })));
        render(<LeitorQr onDecode={() => {}} />);

        await userEvent.click(screen.getByRole('button', { name: 'Abrir câmera' }));

        expect(await screen.findByText(/Permissão de câmera negada/)).toBeTruthy();
        expect(screen.getByText(/NotAllowedError/)).toBeTruthy();
    });

    it('outro erro cai em "sem câmera"', async () => {
        camera(() => Promise.reject(new Error('sem hardware')));
        render(<LeitorQr onDecode={() => {}} />);

        await userEvent.click(screen.getByRole('button', { name: 'Abrir câmera' }));

        expect(await screen.findByText(/Não foi possível abrir a câmera/)).toBeTruthy();
    });
});
