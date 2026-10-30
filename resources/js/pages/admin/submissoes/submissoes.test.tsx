import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';
import { type DetalheSubmissao } from '@/types/admin';

import LancarSubmissao from './lancar';
import MostrarSubmissao from './mostrar';

const detalhe = (extra: Partial<DetalheSubmissao> = {}, equipe: Partial<DetalheSubmissao['equipe']> = {}): DetalheSubmissao => ({
    id: 4,
    titulo: 'App de Rotas',
    resumo: 'Planeja trajetos',
    descricao: 'Detalhes longos',
    repo_url: 'https://github.com/eq/app',
    video_url: null,
    deploy_url: 'https://app.exemplo.com',
    status: 'submitted',
    status_label: 'Enviado',
    origem_label: 'Sistema',
    precisa_conferencia: false,
    enviado_em: '2026-05-10T15:30:00Z',
    versao_atual: 2,
    pode_decidir_atraso: false,
    decisao_atraso: null,
    equipe: {
        id: 7,
        nome: 'Os Devs',
        slug: 'os-devs',
        status: 'confirmed',
        status_label: 'Confirmada',
        pode_desclassificar: false,
        pode_reabilitar: false,
        decisao: null,
        ...equipe,
    },
    trilha: { nome: 'IA', cor: null },
    ...extra,
});

const montar = (
    submissao: DetalheSubmissao,
    versoes = [] as Parameters<typeof MostrarSubmissao>[0]['versoes'],
    arquivos = [] as Parameters<typeof MostrarSubmissao>[0]['arquivos'],
) => render(<MostrarSubmissao submissao={submissao} versoes={versoes} arquivos={arquivos} />);

describe('MostrarSubmissao', () => {
    it('mostra o projeto, a equipe, o status e a data em horário de Brasília', () => {
        montar(detalhe());

        expect(screen.getByRole('heading', { level: 1, name: 'App de Rotas' })).toBeTruthy();
        expect(screen.getByText('Equipe Os Devs')).toBeTruthy();
        expect(screen.getByText('IA')).toBeTruthy();
        expect(screen.getByText('Versão atual: 2')).toBeTruthy();
        expect(screen.getByText(/Enviado em 10\/05\/2026,? 12:30/)).toBeTruthy();
        expect(screen.getByText('Planeja trajetos')).toBeTruthy();
        expect(screen.getByText('Detalhes longos')).toBeTruthy();
    });

    it('sem título nem trilha usa os textos padrão', () => {
        montar(detalhe({ titulo: null, trilha: null, enviado_em: null }));

        expect(screen.getByRole('heading', { level: 1, name: 'Projeto sem título' })).toBeTruthy();
        expect(screen.getByText('Sem trilha')).toBeTruthy();
        expect(screen.getByText('Enviado em —')).toBeTruthy();
    });

    it('links externos abrem com rel seguro e só os preenchidos aparecem', () => {
        montar(detalhe());

        // A sidebar também tem um "Repositório" (do projeto); este é o da equipe.
        const repo = screen.getAllByRole('link', { name: /Repositório/ }).find((l) => l.getAttribute('href') === 'https://github.com/eq/app')!;
        expect(repo.getAttribute('target')).toBe('_blank');
        expect(repo.getAttribute('rel')).toContain('noreferrer');
        expect(screen.getByRole('link', { name: /Deploy/ })).toBeTruthy();
        expect(screen.queryByRole('link', { name: /Vídeo/ })).toBeNull();
    });

    it('marca envio por fora do sistema como ainda não conferido', () => {
        montar(detalhe({ precisa_conferencia: true, origem_label: 'E-mail' }));
        expect(screen.getByRole('status').textContent).toContain('E-mail — ainda não conferido.');
    });

    it('arquivos e histórico vazios explicam o estado', () => {
        montar(detalhe());

        expect(screen.getByText('Nenhum arquivo anexado a esta submissão.')).toBeTruthy();
        expect(screen.getByText(/Ainda não há nenhum envio registrado/)).toBeTruthy();
    });

    it('lista arquivos com download e versões com rótulos em português', () => {
        montar(
            detalhe(),
            [
                {
                    versao: 1,
                    autor: 'Ana',
                    criado_em: '2026-05-10T15:00:00Z',
                    payload: { title: 'App', repo_url: 'https://x', submitted_at: '2026-05-10T15:00:00Z', video_url: null, campo_novo: 'abc' },
                },
            ],
            [{ id: 3, nome: 'slides.pdf', tamanho: '1 MB', versao: 1 }],
        );

        expect(screen.getByText('slides.pdf')).toBeTruthy();
        expect(screen.getByText('1 MB · versão 1')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Baixar slides.pdf' }).getAttribute('href')).toBe('/submission-files/download/3');
        expect(screen.getByText('Título')).toBeTruthy();
        expect(screen.getByText('Repositório', { selector: 'dt' })).toBeTruthy();
        expect(screen.getByText('campo_novo')).toBeTruthy();
        // Data do envio na linha do autor e o `submitted_at` do payload, os dois em Brasília.
        expect(screen.getAllByText(/10\/05\/2026,? 12:00/)).toHaveLength(2);
        expect(screen.getByText('—')).toBeTruthy();
    });

    it('registra a decisão sobre envio atrasado (aceitar por padrão)', async () => {
        montar(detalhe({ pode_decidir_atraso: true, status: 'late', status_label: 'Atrasado' }));

        expect(screen.getByText('Envio fora do prazo')).toBeTruthy();
        await userEvent.type(screen.getByLabelText('Motivo'), 'Queda de rede');
        await userEvent.click(screen.getByRole('radio', { name: 'Recusar o envio' }));
        await userEvent.click(screen.getByRole('button', { name: 'Registrar decisão' }));

        expect(visits).toEqual([
            { method: 'post', url: '/painel/submissions/late-decision/4', data: { decisao: 'recusar', motivo: 'Queda de rede' } },
        ]);
    });

    it('mostra a última decisão sobre o atraso', () => {
        montar(
            detalhe({
                pode_decidir_atraso: true,
                decisao_atraso: { quem: 'Carlos', quando: '2026-05-10T16:00:00Z', motivo: 'Queda de rede' },
            }),
        );
        expect(screen.getByText(/por Carlos em/).textContent).toContain('“Queda de rede”');
    });

    it('quem não decide atraso não vê o bloco', () => {
        montar(detalhe());
        expect(screen.queryByText('Envio fora do prazo')).toBeNull();
    });

    it('desclassifica a equipe com motivo', async () => {
        montar(detalhe({}, { pode_desclassificar: true }));
        expect(screen.queryByRole('button', { name: 'Reabilitar equipe' })).toBeNull();

        await userEvent.type(screen.getByLabelText('Motivo da desclassificação'), 'Plágio');
        await userEvent.click(screen.getByRole('button', { name: 'Desclassificar equipe' }));

        expect(visits).toEqual([{ method: 'post', url: '/painel/teams/disqualify/7', data: { motivo: 'Plágio' } }]);
    });

    it('reabilita a equipe desclassificada e mostra a decisão anterior', async () => {
        montar(
            detalhe(
                {},
                {
                    status: 'disqualified',
                    status_label: 'Desclassificada',
                    pode_reabilitar: true,
                    decisao: { acao: 'Desclassificada', quem: 'Carlos', quando: '2026-05-10T16:00:00Z', motivo: 'Plágio' },
                },
            ),
        );

        expect(screen.getByText(/Situação da equipe: Desclassificada/)).toBeTruthy();
        expect(screen.getByText(/por Carlos em/).textContent).toContain('“Plágio”');
        await userEvent.type(screen.getByLabelText('Motivo da reabilitação'), 'Revisado');
        await userEvent.click(screen.getByRole('button', { name: 'Reabilitar equipe' }));

        expect(visits[0]).toMatchObject({ url: '/painel/teams/reinstate/7', data: { motivo: 'Revisado' } });
    });

    it('mostra o erro de validação do motivo', () => {
        formErrors.motivo = 'Informe o motivo.';
        montar(detalhe({ pode_decidir_atraso: true }, { pode_desclassificar: true }));
        expect(screen.getAllByText('Informe o motivo.').length).toBeGreaterThan(0);
    });
});

describe('LancarSubmissao', () => {
    const fontes = [{ value: 'email', label: 'E-mail' }];

    it('sem equipes pendentes avisa que todas já entregaram', () => {
        render(<LancarSubmissao equipes={[]} fontes={fontes} />);
        expect(screen.getByText('Toda equipe deste evento já tem uma submissão registrada.')).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Lançar submissão' })).toBeNull();
    });

    it('lança convertendo o horário de Brasília para UTC', async () => {
        render(<LancarSubmissao equipes={[{ id: 7, nome: 'Os Devs' }]} fontes={fontes} />);

        await userEvent.selectOptions(screen.getByLabelText('Equipe'), '7');
        await userEvent.selectOptions(screen.getByLabelText('Como a equipe entregou'), 'email');
        await userEvent.type(screen.getByLabelText('Horário real da entrega'), '2026-05-10T12:30');
        await userEvent.type(screen.getByLabelText('Link do repositório'), 'https://github.com/eq/app');
        await userEvent.click(screen.getByRole('button', { name: 'Lançar submissão' }));

        expect(visits).toHaveLength(1);
        expect(visits[0]).toMatchObject({
            method: 'post',
            url: '/painel/submissions/record/store',
            data: { team_id: '7', source: 'email', original_submitted_at: '2026-05-10T15:30:00.000Z', repo_url: 'https://github.com/eq/app' },
        });
    });

    it('horário vazio segue como nulo, não como data inválida', async () => {
        render(<LancarSubmissao equipes={[{ id: 7, nome: 'Os Devs' }]} fontes={fontes} />);
        await userEvent.click(screen.getByRole('button', { name: 'Lançar submissão' }));

        expect((visits[0].data as { original_submitted_at: unknown }).original_submitted_at).toBeNull();
    });

    it('mostra erro da equipe ligado ao campo', () => {
        formErrors.team_id = 'Escolha uma equipe.';
        render(<LancarSubmissao equipes={[{ id: 7, nome: 'Os Devs' }]} fontes={fontes} />);

        expect(screen.getAllByText('Escolha uma equipe.').length).toBeGreaterThan(0);
        expect(screen.getByLabelText('Equipe').getAttribute('aria-describedby')).toBe('team_id-erro');
    });
});
