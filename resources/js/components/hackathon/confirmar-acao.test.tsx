import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import ConfirmarAcao from './confirmar-acao';

const montar = (extra: Partial<React.ComponentProps<typeof ConfirmarAcao>> = {}) => {
    const onConfirmar = vi.fn();
    render(
        <ConfirmarAcao
            trigger={<button>Desqualificar</button>}
            titulo="Desqualificar equipe?"
            descricao="Não dá para desfazer."
            textoConfirmar="Sim, desqualificar"
            onConfirmar={onConfirmar}
            {...extra}
        />,
    );
    return onConfirmar;
};

describe('ConfirmarAcao', () => {
    it('só pede confirmação depois de abrir, e só age ao confirmar', async () => {
        const onConfirmar = montar();
        expect(screen.queryByRole('dialog')).toBeNull();

        await userEvent.click(screen.getByRole('button', { name: 'Desqualificar' }));
        expect(screen.getByRole('dialog', { name: 'Desqualificar equipe?' })).toBeTruthy();
        expect(onConfirmar).not.toHaveBeenCalled();

        await userEvent.click(screen.getByRole('button', { name: 'Sim, desqualificar' }));
        expect(onConfirmar).toHaveBeenCalledOnce();
    });

    it('cancelar não executa a ação', async () => {
        const onConfirmar = montar();
        await userEvent.click(screen.getByRole('button', { name: 'Desqualificar' }));
        await userEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(onConfirmar).not.toHaveBeenCalled();
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('enquanto processa, desabilita o botão e troca o texto', async () => {
        montar({ processando: true });
        await userEvent.click(screen.getByRole('button', { name: 'Desqualificar' }));

        const botao = screen.getByRole('button', { name: 'Processando…' }) as HTMLButtonElement;
        expect(botao.disabled).toBe(true);
    });
});
