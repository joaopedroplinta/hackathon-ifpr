import { act, renderHook } from '@testing-library/react';
import { beforeEach, describe, expect, it } from 'vitest';

import { initializeTheme, useAppearance } from './use-appearance';
import { useMobileNavigation } from './use-mobile-navigation';

describe('useAppearance', () => {
    beforeEach(() => {
        localStorage.clear();
        document.documentElement.classList.remove('dark');
    });

    it('começa em "system" sem preferência salva', () => {
        const { result } = renderHook(() => useAppearance());
        expect(result.current.appearance).toBe('system');
    });

    it('salva a escolha e aplica a classe dark', () => {
        const { result } = renderHook(() => useAppearance());

        act(() => result.current.updateAppearance('dark'));
        expect(localStorage.getItem('appearance')).toBe('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        act(() => result.current.updateAppearance('light'));
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('lê a preferência salva ao montar', () => {
        localStorage.setItem('appearance', 'dark');
        const { result } = renderHook(() => useAppearance());
        expect(result.current.appearance).toBe('dark');
    });

    it('initializeTheme aplica o tema salvo', () => {
        localStorage.setItem('appearance', 'dark');
        initializeTheme();
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });
});

describe('useMobileNavigation', () => {
    it('libera o pointer-events travado pelo menu', () => {
        document.body.style.pointerEvents = 'none';
        const { result } = renderHook(() => useMobileNavigation());
        result.current();
        expect(document.body.style.pointerEvents).toBe('');
    });
});
