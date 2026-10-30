import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';

import IncidentesIndex from './index';

const tipos = [{ value: 'rede', label: 'Queda de rede' }];
const montar = (extra: Partial<Parameters<typeof IncidentesIndex>[0]> = {}) =>
    render(<IncidentesIndex incidentes={[]} tipos={tipos} prazo_original={null} prazo_efetivo={null} {...extra} />);

describe('IncidentesIndex', () => {
    it('sem incidentes mostra o estado vazio e sem prazo não mostra a faixa', () => {
        montar();
        expect(screen.getByText('Nenhum incidente registrado.')).toBeTruthy();
        expect(screen.queryByText(/Prazo de submissão original/)).toBeNull();
    });

    it('mostra o prazo original e só destaca o efetivo quando mudou', () => {
        const { unmount } = montar({ prazo_original: '10/05 18:00', prazo_efetivo: '10/05 18:00' });
        expect(screen.getByText('10/05 18:00')).toBeTruthy();
        expect(screen.queryByText(/efetivo agora/)).toBeNull();
        unmount();

        montar({ prazo_original: '10/05 18:00', prazo_efetivo: '10/05 19:00' });
        expect(screen.getByText(/efetivo agora/)).toBeTruthy();
        expect(screen.getByText('10/05 19:00')).toBeTruthy();
    });

    it('lista o histórico com autor e a extensão só quando houve', () => {
        montar({
            incidentes: [
                {
                    id: 1,
                    tipo_label: 'Queda de rede',
                    descricao: 'Wi-Fi caiu',
                    extensao_minutos: 30,
                    declarado_por: 'Carlos',
                    declarado_em: '10/05 12:00',
                },
                { id: 2, tipo_label: 'Outro', descricao: 'Sem extensão', extensao_minutos: 0, declarado_por: 'Ana', declarado_em: '10/05 13:00' },
            ],
        });

        expect(screen.getByText('Declarado por Carlos · extensão de 30 min')).toBeTruthy();
        expect(screen.getByText('Declarado por Ana')).toBeTruthy();
    });

    it('incidente sem extensão é registrado direto, sem confirmação', async () => {
        montar();
        await userEvent.selectOptions(screen.getByLabelText('Tipo'), 'rede');
        await userEvent.type(screen.getByLabelText('O que aconteceu'), 'Wi-Fi caiu');
        await userEvent.click(screen.getByRole('button', { name: 'Declarar incidente' }));

        expect(screen.queryByRole('dialog')).toBeNull();
        expect(visits).toEqual([
            { method: 'post', url: '/painel/incidentes/store', data: { kind: 'rede', description: 'Wi-Fi caiu', deadline_extension_minutes: '0' } },
        ]);
    });

    it('extensão de prazo exige confirmação porque vale para todas as equipes', async () => {
        montar();
        await userEvent.selectOptions(screen.getByLabelText('Tipo'), 'rede');
        await userEvent.type(screen.getByLabelText('O que aconteceu'), 'Wi-Fi caiu');
        const minutos = screen.getByLabelText(/Extensão de prazo/);
        await userEvent.clear(minutos);
        await userEvent.type(minutos, '30');
        await userEvent.click(screen.getByRole('button', { name: 'Declarar incidente' }));

        const dialogo = screen.getByRole('dialog', { name: 'Aplicar extensão para todas as equipes?' });
        expect(visits).toHaveLength(0);

        await userEvent.click(within(dialogo).getByRole('button', { name: 'Confirmar extensão' }));
        expect(visits).toHaveLength(1);
        expect(visits[0].data).toMatchObject({ deadline_extension_minutes: '30' });
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('"Revisar dados" volta ao formulário sem registrar', async () => {
        montar();
        const minutos = screen.getByLabelText(/Extensão de prazo/);
        await userEvent.clear(minutos);
        await userEvent.type(minutos, '15');
        await userEvent.click(screen.getByRole('button', { name: 'Declarar incidente' }));
        await userEvent.click(screen.getByRole('button', { name: 'Revisar dados' }));

        expect(visits).toHaveLength(0);
        expect(screen.queryByRole('dialog')).toBeNull();
    });

    it('mostra erro de validação ligado ao campo', () => {
        formErrors.kind = 'Escolha o tipo.';
        montar();
        expect(screen.getAllByText('Escolha o tipo.').length).toBeGreaterThan(0);
        expect(screen.getByLabelText('Tipo').getAttribute('aria-describedby')).toBe('kind-erro');
    });
});
