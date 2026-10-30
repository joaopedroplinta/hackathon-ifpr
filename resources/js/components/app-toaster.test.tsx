import { render } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import AppToaster from './app-toaster';

describe('AppToaster', () => {
    it('monta o Toaster sem erro', () => {
        const { container } = render(<AppToaster />);
        expect(container).toBeTruthy();
    });
});
