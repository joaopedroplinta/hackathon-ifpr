import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import ResumoErro from './resumo-erro';

describe('ResumoErro', () => {
    it('não renderiza nada quando não há erro', () => {
        const { container } = render(<ResumoErro erros={{ nome: undefined, email: '' }} />);
        expect(container.innerHTML).toBe('');
    });

    it('lista só as mensagens preenchidas, com o título padrão, em um alerta', () => {
        render(<ResumoErro erros={{ nome: 'Informe o nome.', email: undefined, senha: 'Senha curta.' }} />);

        expect(screen.getByRole('alert').textContent).toContain('Corrija os campos destacados');
        expect(screen.getAllByRole('listitem').map((li) => li.textContent)).toEqual(['Informe o nome.', 'Senha curta.']);
    });

    it('aceita um título próprio', () => {
        render(<ResumoErro titulo="Algo deu errado" erros={{ a: 'x' }} />);
        expect(screen.getByRole('alert').textContent).toContain('Algo deu errado');
    });
});
