import { renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { useInitials } from './use-initials';

describe('useInitials', () => {
    const getInitials = () => renderHook(() => useInitials()).result.current;

    it('usa a primeira e a última letra do nome completo, em maiúscula', () => {
        expect(getInitials()('joão pedro plinta')).toBe('JP');
    });

    it('devolve só uma inicial quando há um nome só', () => {
        expect(getInitials()('maria')).toBe('M');
    });

    it('ignora espaços nas pontas', () => {
        expect(getInitials()('  Ana Souza  ')).toBe('AS');
    });
});
