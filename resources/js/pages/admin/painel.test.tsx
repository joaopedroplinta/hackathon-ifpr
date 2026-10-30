import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';

import { formErrors, visits } from '@/test/inertia';

import ConfirmarCheckin from './checkin/confirmar';
import CheckinIndex from './checkin/index';
import AdminDashboard from './index';
import SemEvento from './sem-evento';

// A câmera não existe em teste: o leitor vira dois botões que entregam um texto lido.
vi.mock('@/components/hackathon/leitor-qr', () => ({
    default: ({ onDecode }: { onDecode: (t: string) => void }) => (
        <>
            <button onClick={() => onDecode(`${window.location.origin}/painel/checkin/confirmar/5?via=qr`)}>ler QR do sistema</button>
            <button onClick={() => onDecode('https://outro-site.com/x')}>ler QR de fora</button>
            <button onClick={() => onDecode('texto solto')}>ler texto solto</button>
        </>
    ),
}));

describe('AdminDashboard', () => {
    const base = { evento: { nome: 'Hackathon 2026' }, inscritos: 40, equipes_sem_submissao: 0, atribuicoes_em_aberto: 0, presenca_hoje: 12 };

    it('tudo em dia: sem pendências, com os números do dia', () => {
        render(<AdminDashboard {...base} />);

        expect(screen.getByRole('heading', { level: 1, name: 'Olá, Maria.' })).toBeTruthy();
        expect(screen.getByText('Visão geral de Hackathon 2026.')).toBeTruthy();
        expect(screen.getByText('Tudo em dia — nenhuma submissão ou avaliação pendente.')).toBeTruthy();
        expect(screen.getByText('40')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Presenças hoje/ }).getAttribute('href')).toBe('/painel/checkin/index');
        expect(screen.getByRole('link', { name: /Fazer check-in/ })).toBeTruthy();
    });

    it('lista só as pendências que existem, com singular e plural', () => {
        render(<AdminDashboard {...base} equipes_sem_submissao={1} atribuicoes_em_aberto={3} />);

        expect(screen.getByText('1 equipe sem submissão')).toBeTruthy();
        expect(screen.getByText('3 avaliações em aberto')).toBeTruthy();
        expect(screen.queryByText(/Tudo em dia/)).toBeNull();
    });

    it('só a pendência com fila aparece', () => {
        render(<AdminDashboard {...base} atribuicoes_em_aberto={1} />);
        expect(screen.getByText('1 avaliação em aberto')).toBeTruthy();
        expect(screen.queryByText(/sem submissão/)).toBeNull();
    });
});

describe('SemEvento', () => {
    it('explica o que fazer: criar o primeiro evento', () => {
        render(<SemEvento />);

        expect(screen.getByRole('heading', { level: 1, name: 'Nenhum evento cadastrado ainda' })).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Criar evento' }).getAttribute('href')).toBe('/painel/evento/create');
    });
});

describe('CheckinIndex', () => {
    const checkpoints = [{ id: 1, nome: 'Entrada', tipo_label: 'Entrada do evento' }];
    const opcoes = { tipos: [{ valor: 'entrada', rotulo: 'Entrada' }] };

    it('sem checkpoint, só deixa criar o primeiro', async () => {
        render(<CheckinIndex checkpoints={[]} opcoes={opcoes} busca={null} resultados={[]} />);

        expect(screen.getByText('Nenhum checkpoint cadastrado ainda')).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'ler QR do sistema' })).toBeNull();

        await userEvent.type(screen.getByLabelText('Nome'), 'Entrada');
        await userEvent.selectOptions(screen.getByLabelText('Tipo'), 'entrada');
        await userEvent.click(screen.getByRole('button', { name: 'Criar' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/checkin/checkpoints/store', data: { name: 'Entrada', type: 'entrada' } }]);
    });

    it('com checkpoint, mostra os pontos, o leitor e a busca', () => {
        render(<CheckinIndex checkpoints={checkpoints} opcoes={opcoes} busca={null} resultados={[]} />);

        expect(screen.getByText('Entrada · Entrada do evento')).toBeTruthy();
        expect(screen.getByRole('button', { name: 'ler QR do sistema' })).toBeTruthy();
        expect(screen.queryByText(/Ninguém inscrito/)).toBeNull();
    });

    it('QR do próprio sistema navega para a confirmação', async () => {
        render(<CheckinIndex checkpoints={checkpoints} opcoes={opcoes} busca={null} resultados={[]} />);
        await userEvent.click(screen.getByRole('button', { name: 'ler QR do sistema' }));

        expect(visits).toEqual([{ method: 'visit', url: '/painel/checkin/confirmar/5?via=qr', data: undefined }]);
        expect(screen.queryByRole('alert')).toBeNull();
    });

    it.each(['ler QR de fora', 'ler texto solto'])('"%s" é recusado com aviso', async (botao) => {
        render(<CheckinIndex checkpoints={checkpoints} opcoes={opcoes} busca={null} resultados={[]} />);
        await userEvent.click(screen.getByRole('button', { name: botao }));

        expect(screen.getByRole('alert').textContent).toContain('não pertence a este sistema');
        expect(visits).toHaveLength(0);
    });

    it('busca por nome faz GET com o termo', async () => {
        render(<CheckinIndex checkpoints={checkpoints} opcoes={opcoes} busca={null} resultados={[]} />);
        await userEvent.type(screen.getByLabelText('Buscar por nome'), 'ana');
        await userEvent.click(screen.getByRole('button', { name: 'Buscar' }));

        expect(visits).toEqual([{ method: 'get', url: '/painel/checkin/index', data: { busca: 'ana' } }]);
    });

    it('resultado da busca leva à confirmação; sem resultado avisa', () => {
        const { unmount } = render(
            <CheckinIndex
                checkpoints={checkpoints}
                opcoes={opcoes}
                busca="ana"
                resultados={[{ id: 1, nome: 'Ana Lima', email: 'ana@x.com', confirmar_href: '/painel/checkin/confirmar/1' }]}
            />,
        );
        expect(screen.getByText('Ana Lima')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Confirmar' }).getAttribute('href')).toBe('/painel/checkin/confirmar/1');
        unmount();

        render(<CheckinIndex checkpoints={checkpoints} opcoes={opcoes} busca="zzz" resultados={[]} />);
        expect(screen.getByText(/Ninguém inscrito neste evento bate com "zzz"/)).toBeTruthy();
    });
});

describe('ConfirmarCheckin', () => {
    const participante = { id: 1, nome: 'Ana Lima', email: 'ana@x.com', avatar_url: null };
    const checkpoints = [
        { id: 1, nome: 'Entrada', tipo_label: 'Entrada' },
        { id: 2, nome: 'Oficina', tipo_label: 'Oficina' },
    ];
    const montar = (extra: Partial<Parameters<typeof ConfirmarCheckin>[0]> = {}) =>
        render(
            <ConfirmarCheckin
                participante={participante}
                checkpoints={checkpoints}
                checkpoint_selecionado_id={null}
                ja_confirmado={false}
                confirmado_em={null}
                confirmado_por={null}
                via={null}
                confirmar_url="/painel/checkin/confirmar/1"
                {...extra}
            />,
        );

    it('confirma a presença no checkpoint escolhido', async () => {
        montar({ checkpoint_selecionado_id: 2, via: 'busca' });

        expect((screen.getByLabelText('Checkpoint') as HTMLSelectElement).value).toBe('2');
        await userEvent.click(screen.getByRole('button', { name: 'Confirmar presença' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/checkin/confirmar/1', data: { checkpoint_id: '2', via: 'busca' } }]);
    });

    it('já confirmada: mostra quando e por quem, sem formulário', () => {
        montar({ ja_confirmado: true, confirmado_em: '2026-05-10T15:30:00Z', confirmado_por: 'Carlos' });

        expect(screen.getByText('Presença já confirmada')).toBeTruthy();
        expect(screen.getByText(/10\/05\/2026,? 12:30 · por Carlos/)).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Confirmar presença' })).toBeNull();
    });

    it('sem checkpoint, leva a criar um', () => {
        montar({ checkpoints: [] });
        expect(screen.getByRole('link', { name: 'Criar um agora' }).getAttribute('href')).toBe('/painel/checkin/index');
    });

    it('mostra foto quando existe e o erro de checkpoint', () => {
        formErrors.checkpoint_id = 'Checkpoint inválido.';
        const { container } = montar({ participante: { ...participante, avatar_url: '/a.png' } });

        expect(container.querySelector('img')?.getAttribute('src')).toBe('/a.png');
        expect(screen.getAllByText('Checkpoint inválido.').length).toBeGreaterThan(0);
    });
});
