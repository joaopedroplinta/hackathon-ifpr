import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { visits } from '@/test/inertia';

import CertificadosIndex from './index';

const props = {
    certificados: [],
    pessoas: [{ id: 3, nome: 'Ana Lima' }],
    tipos: [
        { value: 'participacao', label: 'Participação' },
        { value: 'colocacao', label: 'Colocação' },
    ],
};

describe('CertificadosIndex (organização)', () => {
    it('sem certificados orienta emitir em lote ou avulso', () => {
        render(<CertificadosIndex {...props} />);
        expect(screen.getByText('Nenhum certificado emitido ainda.')).toBeTruthy();
    });

    it('lista emitidos e distingue PDF pronto de em geração', () => {
        render(
            <CertificadosIndex
                {...props}
                certificados={[
                    {
                        id: 1,
                        nome: 'Ana Lima',
                        tipo: 'participacao',
                        tipo_label: 'Participação',
                        pronto: true,
                        emitido_em: '10/05/2026',
                        codigo: 'abc',
                    },
                    { id: 2, nome: 'Bruno', tipo: 'colocacao', tipo_label: 'Colocação', pronto: false, emitido_em: '10/05/2026', codigo: 'def' },
                ]}
            />,
        );

        expect(screen.getByText('Pronto')).toBeTruthy();
        expect(screen.getByText('Gerando…')).toBeTruthy();
    });

    it('emite certificado avulso', async () => {
        render(<CertificadosIndex {...props} />);
        await userEvent.selectOptions(screen.getByLabelText('Pessoa'), '3');
        await userEvent.selectOptions(screen.getByLabelText('Tipo'), 'participacao');
        await userEvent.click(screen.getByRole('button', { name: 'Emitir' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/certificados/store', data: { user_id: '3', type: 'participacao', colocacao: '' } }]);
    });

    it('o texto da colocação só aparece para o tipo colocação', async () => {
        render(<CertificadosIndex {...props} />);
        expect(screen.queryByLabelText('Texto da colocação')).toBeNull();

        await userEvent.selectOptions(screen.getByLabelText('Tipo'), 'colocacao');
        await userEvent.type(screen.getByLabelText('Texto da colocação'), '1º lugar geral');
        await userEvent.click(screen.getByRole('button', { name: 'Emitir' }));

        expect(visits[0].data).toMatchObject({ type: 'colocacao', colocacao: '1º lugar geral' });
    });
});
