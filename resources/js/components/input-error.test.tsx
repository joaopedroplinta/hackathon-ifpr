import { render, screen } from '@testing-library/react';
import { Check } from 'lucide-react';
import { describe, expect, it } from 'vitest';

import { Icon } from './icon';
import InputError from './input-error';
import TextLink from './text-link';

describe('InputError', () => {
    it('mostra a mensagem', () => {
        render(<InputError message="O e-mail é obrigatório." id="erro" />);
        expect(screen.getByText('O e-mail é obrigatório.').id).toBe('erro');
    });

    it('não renderiza nada sem mensagem', () => {
        const { container } = render(<InputError />);
        expect(container.firstChild).toBeNull();
    });
});

describe('TextLink', () => {
    it('é um link interno com a classe de sublinhado', () => {
        render(<TextLink href="/entrar">Entrar</TextLink>);
        const link = screen.getByRole('link', { name: 'Entrar' });
        expect(link.getAttribute('href')).toBe('/entrar');
        expect(link.className).toContain('underline');
    });
});

describe('Icon', () => {
    it('aplica o tamanho padrão e aceita classe extra', () => {
        const { container } = render(<Icon iconNode={Check} className="text-red-500" />);
        const svg = container.querySelector('svg');
        expect(svg?.getAttribute('class')).toContain('h-4 w-4');
        expect(svg?.getAttribute('class')).toContain('text-red-500');
    });
});
