import { renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { useIsMobile } from './use-mobile';

function mockViewport(largura: number) {
    Object.defineProperty(window, 'innerWidth', { configurable: true, value: largura });
    const remove = vi.fn();
    window.matchMedia = vi.fn().mockReturnValue({ addEventListener: vi.fn(), removeEventListener: remove }) as unknown as typeof window.matchMedia;

    return remove;
}

describe('useIsMobile', () => {
    afterEach(() => vi.restoreAllMocks());

    it('é verdadeiro abaixo de 768px', () => {
        mockViewport(767);
        expect(renderHook(() => useIsMobile()).result.current).toBe(true);
    });

    it('é falso a partir de 768px', () => {
        mockViewport(768);
        expect(renderHook(() => useIsMobile()).result.current).toBe(false);
    });

    it('remove o listener ao desmontar', () => {
        const remove = mockViewport(1024);
        renderHook(() => useIsMobile()).unmount();
        expect(remove).toHaveBeenCalledOnce();
    });
});
