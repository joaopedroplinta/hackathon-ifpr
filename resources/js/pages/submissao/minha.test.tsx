import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';
import { type Submissao } from '@/types/submissao';

import MinhaSubmissao from './minha';

const submissao = (extra: Partial<Submissao> = {}): Submissao => ({
    title: 'App de Rotas',
    summary: 'Planeja trajetos',
    description: 'Detalhes',
    repo_url: 'https://github.com/eq/app',
    video_url: null,
    deploy_url: null,
    status: 'submitted',
    status_label: 'Enviado',
    enviado_em: '2026-05-10T15:30:00Z',
    versao_atual: 2,
    foi_enviada: true,
    fora_do_prazo: false,
    decisao_atraso: null,
    ...extra,
});

const montar = (props: Partial<Parameters<typeof MinhaSubmissao>[0]> = {}) =>
    render(
        <MinhaSubmissao
            equipe={{ nome: 'Os Devs', slug: 'os-devs' }}
            submissao={null}
            arquivos={{ limite: 5, itens: [] }}
            versoes={[]}
            prazo={{ encerra_em: null, aberto: true }}
            pode_editar
            motivo_bloqueio={null}
            {...props}
        />,
    );

describe('MinhaSubmissao', () => {
    it('sem projeto: orienta a preencher e começa com 0/3 essenciais', () => {
        montar();

        expect(screen.getByRole('heading', { level: 1, name: 'Projeto da Os Devs' })).toBeTruthy();
        expect(screen.getByText(/Nenhum projeto enviado ainda/)).toBeTruthy();
        expect(screen.getByRole('progressbar', { name: 'Campos essenciais preenchidos' }).getAttribute('aria-valuenow')).toBe('0');
        expect(screen.getByRole('button', { name: 'Enviar projeto' })).toBeTruthy();
    });

    it('acompanha o preenchimento dos campos essenciais', async () => {
        montar();
        await userEvent.type(screen.getByLabelText('Título do projeto'), 'Meu app');
        await userEvent.type(screen.getByLabelText('Resumo'), 'Resumo');

        expect(screen.getByRole('progressbar', { name: 'Campos essenciais preenchidos' }).getAttribute('aria-valuenow')).toBe('2');
        expect(screen.getByText('2/3')).toBeTruthy();
    });

    it('envia o projeto e salva rascunho em rotas diferentes', async () => {
        montar();
        await userEvent.type(screen.getByLabelText('Título do projeto'), 'Meu app');

        await userEvent.click(screen.getByRole('button', { name: 'Salvar rascunho' }));
        await userEvent.click(screen.getByRole('button', { name: 'Enviar projeto' }));

        expect(visits.map((v) => [v.method, v.url])).toEqual([
            ['post', '/submissions/save'],
            ['post', '/submissions/submit'],
        ]);
        expect(visits[1].data).toMatchObject({ title: 'Meu app' });
    });

    it('rascunho salvo mas não enviado avisa que só o envio conta', () => {
        montar({ submissao: submissao({ foi_enviada: false, status: 'draft', status_label: 'Rascunho' }) });
        expect(screen.getByText(/ainda não foi enviado/)).toBeTruthy();
    });

    it('envio feito mostra versão, horário de Brasília e oferece reenviar', () => {
        montar({ submissao: submissao() });

        expect(screen.getByRole('status').textContent).toContain('Enviado em');
        expect(screen.getByRole('status').textContent).toContain('12:30');
        expect(screen.getByRole('status').textContent).toContain('versão 2');
        expect(screen.getByRole('button', { name: 'Reenviar projeto' })).toBeTruthy();
    });

    it.each([
        ['aceito', 'A organização aceitou o envio'],
        ['recusado', 'A organização recusou o envio'],
        [null, 'a organização vai avaliar o caso'],
    ] as const)('envio atrasado com decisão %s explica o resultado', (decisao, texto) => {
        montar({ submissao: submissao({ fora_do_prazo: true, status_label: 'Atrasado', decisao_atraso: decisao }) });
        expect(screen.getByRole('status').textContent).toContain(texto);
    });

    it('prazo encerrado: mostra o motivo e o resumo somente leitura, sem formulário', () => {
        montar({ pode_editar: false, motivo_bloqueio: 'O prazo encerrou.', submissao: submissao({ video_url: 'https://youtu.be/x' }) });

        expect(screen.getByRole('alert').textContent).toContain('O prazo encerrou.');
        expect(screen.queryByRole('button', { name: /Enviar projeto|Reenviar/ })).toBeNull();
        expect(screen.getByRole('link', { name: 'https://github.com/eq/app' }).getAttribute('rel')).toContain('noopener');
        expect(screen.getByRole('link', { name: 'https://youtu.be/x' })).toBeTruthy();
        expect(screen.getByText('Título')).toBeTruthy();
        expect(screen.getAllByText('Não informado').length).toBeGreaterThan(0);
    });

    it('bloqueado sem motivo usa a mensagem padrão; sem projeto avisa que não há registro', () => {
        montar({ pode_editar: false });
        expect(screen.getByRole('alert').textContent).toContain('não pode mais ser alterado');
        expect(screen.getByText('Não há projeto registrado para esta equipe.')).toBeTruthy();
    });

    it('mostra o histórico de versões e os arquivos', () => {
        montar({
            versoes: [{ versao: 1, autor: 'Ana', criado_em: '2026-05-10T15:00:00Z' }],
            arquivos: { limite: 5, itens: [{ id: 1, nome: 'slides.pdf', tamanho: '1 MB', versao: 1, pode_remover: false }] },
        });

        expect(screen.getByText('Versão 1')).toBeTruthy();
        expect(screen.getByText('slides.pdf')).toBeTruthy();
    });

    it('mostra erro de validação do repositório', () => {
        formErrors.repo_url = 'Informe o link do repositório.';
        montar();
        expect(screen.getAllByText('Informe o link do repositório.').length).toBeGreaterThan(0);
        expect(screen.getByLabelText('Link do repositório').getAttribute('aria-describedby')).toBe('repo_url-erro');
    });
});
