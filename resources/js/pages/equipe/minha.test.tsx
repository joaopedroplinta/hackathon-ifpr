import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { setPage, visits } from '@/test/inertia';

import MinhaEquipe from './minha';

type Membro = Parameters<typeof MinhaEquipe>[0]['equipe']['membros'][number];

const membro = (id: number, extra: Partial<Membro> = {}): Membro => ({
    id,
    nome: `Pessoa ${id}`,
    email: `p${id}@ifpr.edu.br`,
    avatar: null,
    papel: 'membro',
    e_lider: false,
    sou_eu: false,
    pode_remover: false,
    pode_sair: false,
    ...extra,
});

const montar = (membros: Membro[], extra: Partial<Parameters<typeof MinhaEquipe>[0]> = {}) => {
    setPage({ convites: { pode_convidar: false, motivo_bloqueio: null, pendentes: [] } });
    return render(
        <MinhaEquipe
            equipe={{
                nome: 'Os Devs',
                descricao: 'Fazemos apps',
                codigo_convite: 'AS3DYP',
                status: 'active',
                status_label: 'Ativa',
                trilha: { id: 1, name: 'IA', color: null },
                sou_lider: true,
                slug: 'os-devs',
                membros,
            }}
            limites={{ minimo: 3, maximo: 5, atual: membros.length }}
            pode_editar
            pode_transferir={false}
            {...extra}
        />,
    );
};

describe('MinhaEquipe', () => {
    it('mostra nome, trilha, status, descrição e o código de convite', () => {
        montar([membro(1, { e_lider: true, sou_eu: true })]);

        expect(screen.getByRole('heading', { level: 1, name: 'Os Devs' })).toBeTruthy();
        expect(screen.getByText('Trilha: IA · Ativa')).toBeTruthy();
        expect(screen.getByText('Fazemos apps')).toBeTruthy();
        expect(screen.getByText('AS3DYP')).toBeTruthy();
    });

    it('sem trilha avisa que não foi definida', () => {
        const { unmount } = montar([membro(1)]);
        unmount();
        setPage({ convites: { pode_convidar: false, motivo_bloqueio: null, pendentes: [] } });
        render(
            <MinhaEquipe
                equipe={{
                    nome: 'X',
                    descricao: null,
                    codigo_convite: 'A',
                    status: 'a',
                    status_label: 'Ativa',
                    trilha: null,
                    sou_lider: false,
                    slug: 'x',
                    membros: [membro(1)],
                }}
                limites={{ minimo: 1, maximo: 5, atual: 1 }}
                pode_editar={false}
                pode_transferir={false}
            />,
        );
        expect(screen.getByText('Sem trilha definida · Ativa')).toBeTruthy();
    });

    it('copia o código de convite', async () => {
        const user = userEvent.setup();
        montar([membro(1)]);

        await user.click(screen.getByRole('button', { name: /Copiar/ }));

        expect(await screen.findByText('Copiado')).toBeTruthy();
        expect(await navigator.clipboard.readText()).toBe('AS3DYP');
    });

    it('avisa quando copiar não é possível', async () => {
        montar([membro(1)]);
        Object.defineProperty(navigator, 'clipboard', { configurable: true, value: undefined });

        await userEvent.click(screen.getByRole('button', { name: /Copiar/ }));

        expect(await screen.findByRole('alert')).toBeTruthy();
        expect(screen.getByText(/Selecione e copie o código manualmente/)).toBeTruthy();
    });

    it('mostra a ocupação e quantos faltam para o mínimo (singular/plural)', () => {
        const { unmount } = montar([membro(1), membro(2)]);
        expect(screen.getByRole('progressbar', { name: 'Ocupação da equipe' }).getAttribute('aria-valuenow')).toBe('2');
        expect(screen.getByText('Falta 1 integrante para atingir o mínimo.')).toBeTruthy();
        unmount();

        montar([membro(1)]);
        expect(screen.getByText('Faltam 2 integrantes para atingir o mínimo.')).toBeTruthy();
    });

    it('no mínimo, não mostra o aviso de faltantes', () => {
        montar([membro(1), membro(2), membro(3)]);
        expect(screen.queryByText(/para atingir o mínimo/)).toBeNull();
    });

    it('líder remove um integrante só depois de confirmar', async () => {
        montar([membro(1, { e_lider: true, sou_eu: true }), membro(2, { pode_remover: true })]);

        await userEvent.click(screen.getByRole('button', { name: 'Remover Pessoa 2' }));
        const dialogo = screen.getByRole('dialog', { name: 'Remover Pessoa 2?' });
        expect(visits).toHaveLength(0);

        await userEvent.click(within(dialogo).getByRole('button', { name: 'Remover integrante' }));
        expect(visits).toEqual([{ method: 'delete', url: '/teams/members/remove/2', data: undefined }]);
    });

    it('não oferece remover quem não pode ser removido', () => {
        montar([membro(1, { e_lider: true, sou_eu: true }), membro(2)]);
        expect(screen.queryByRole('button', { name: /Remover Pessoa/ })).toBeNull();
        expect(screen.getByText('Líder')).toBeTruthy();
    });

    it('integrante sai da equipe depois de confirmar; sozinho, avisa que a equipe se desfaz', async () => {
        montar([membro(1, { sou_eu: true, pode_sair: true })]);

        expect(screen.getByText(/única pessoa na equipe/)).toBeTruthy();
        await userEvent.click(screen.getByRole('button', { name: 'Sair da equipe' }));
        await userEvent.click(within(screen.getByRole('dialog')).getByRole('button', { name: 'Sair da equipe' }));

        expect(visits).toEqual([{ method: 'delete', url: '/teams/leave/1', data: undefined }]);
    });

    it('quem não pode sair não vê a seção', () => {
        montar([membro(1, { sou_eu: true, pode_sair: false }), membro(2)]);
        expect(screen.queryByText('Sair da equipe')).toBeNull();
    });

    it('líder transfere a liderança para o integrante escolhido', async () => {
        montar([membro(1, { e_lider: true, sou_eu: true }), membro(2), membro(3)], { pode_transferir: true });

        const transferir = screen.getByRole('button', { name: 'Transferir liderança' }) as HTMLButtonElement;
        expect(transferir.disabled).toBe(true);

        await userEvent.selectOptions(screen.getByLabelText('Novo líder'), '3');
        await userEvent.click(transferir);

        expect(visits).toEqual([{ method: 'patch', url: '/teams/leadership/update/os-devs', data: { membership_id: 3 } }]);
    });

    it('sem outro integrante não há para quem transferir', () => {
        montar([membro(1, { e_lider: true, sou_eu: true })], { pode_transferir: true });
        expect(screen.queryByText('Passar a liderança')).toBeNull();
    });
});
