import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, visits } from '@/test/inertia';
import { type EventoExistente } from '@/types/evento-admin';

import CriarEvento from './criar';
import EditarEvento from './editar';

const campo = (rotulo: string, id: string) => screen.getByLabelText(rotulo, { selector: `#${id}` });

describe('CriarEvento', () => {
    it('explica que é o primeiro evento da instalação', () => {
        render(<CriarEvento />);
        expect(screen.getByRole('heading', { level: 1, name: 'Criar evento' })).toBeTruthy();
        expect(screen.getByText(/Ainda não existe nenhuma edição/)).toBeTruthy();
    });

    it('começa com equipe de 1 a 5 pessoas e sem coletas opcionais', () => {
        render(<CriarEvento />);

        expect((screen.getByLabelText('Mínimo') as HTMLInputElement).value).toBe('1');
        expect((screen.getByLabelText('Máximo') as HTMLInputElement).value).toBe('5');
        expect((screen.getByLabelText('Solicitar tamanho de camiseta') as HTMLInputElement).checked).toBe(false);
        expect((screen.getByLabelText('Solicitar restrições alimentares') as HTMLInputElement).checked).toBe(false);
    });

    it('envia as datas em UTC (Brasília -03:00) e deixa em branco o que não foi preenchido', async () => {
        render(<CriarEvento />);

        await userEvent.type(screen.getByLabelText('Nome do evento'), 'Hackathon 2026');
        await userEvent.type(campo('Início', 'starts_at'), '2026-05-10T09:00');
        await userEvent.type(screen.getByLabelText('Prazo de submissão'), '2026-05-10T18:00');
        await userEvent.click(screen.getByLabelText('Solicitar tamanho de camiseta'));
        await userEvent.click(screen.getByRole('button', { name: 'Criar evento' }));

        expect(visits).toHaveLength(1);
        expect(visits[0]).toMatchObject({
            method: 'post',
            url: '/painel/evento/store',
            data: {
                name: 'Hackathon 2026',
                starts_at: '2026-05-10T12:00:00.000Z',
                submission_deadline: '2026-05-10T21:00:00.000Z',
                ends_at: null,
                voting_opens_at: null,
                collect_shirt_size: true,
                collect_dietary_notes: false,
            },
        });
    });

    it('mostra erro do nome ligado ao campo', () => {
        formErrors.name = 'Informe o nome do evento.';
        render(<CriarEvento />);

        expect(screen.getAllByText('Informe o nome do evento.').length).toBeGreaterThan(0);
        expect(screen.getByLabelText('Nome do evento').getAttribute('aria-describedby')).toBe('name-erro');
    });
});

describe('EditarEvento', () => {
    const evento: EventoExistente = {
        name: 'Hackathon 2026',
        description: 'Mobilidade urbana',
        status: 'published',
        registration_opens_at: '2026-04-01T13:00:00Z',
        registration_closes_at: null,
        starts_at: '2026-05-10T12:00:00Z',
        ends_at: null,
        submission_deadline: '2026-05-10T21:00:00Z',
        voting_opens_at: null,
        voting_closes_at: null,
        min_team_size: 2,
        max_team_size: 5,
        collect_shirt_size: true,
        collect_dietary_notes: false,
        certificate_signer_name: 'Profa. Lima',
        certificate_signer_role: 'Coordenadora',
        certificate_accent_color: null,
    };
    const montar = (extra: Partial<Parameters<typeof EditarEvento>[0]> = {}) =>
        render(
            <EditarEvento
                evento={evento}
                status_opcoes={[
                    { value: 'draft', label: 'Rascunho' },
                    { value: 'published', label: 'Publicado' },
                ]}
                regulamento={{ nome_arquivo: null, atualizado_em: null }}
                certificado_logo={{ tem_logo: false }}
                {...extra}
            />,
        );

    it('preenche o formulário com o evento, mostrando datas em horário de Brasília', () => {
        montar();

        expect((screen.getByLabelText('Nome do evento') as HTMLInputElement).value).toBe('Hackathon 2026');
        expect((screen.getByLabelText('Tema / desafio') as HTMLTextAreaElement).value).toBe('Mobilidade urbana');
        expect((screen.getByLabelText('Fase') as HTMLSelectElement).value).toBe('published');
        expect((campo('Início', 'starts_at') as HTMLInputElement).value).toBe('2026-05-10T09:00');
        expect((campo('Abre em', 'registration_opens_at') as HTMLInputElement).value).toBe('2026-04-01T10:00');
        expect((campo('Fecha em', 'registration_closes_at') as HTMLInputElement).value).toBe('');
        expect((screen.getByLabelText('Mínimo') as HTMLInputElement).value).toBe('2');
        expect((screen.getByLabelText('Solicitar tamanho de camiseta') as HTMLInputElement).checked).toBe(true);
        expect((screen.getByLabelText('Nome de quem assina') as HTMLInputElement).value).toBe('Profa. Lima');
    });

    it('salva por PATCH devolvendo as datas em UTC e as vazias como nulas', async () => {
        montar();
        await userEvent.click(screen.getByRole('button', { name: 'Salvar alterações' }));

        expect(visits).toHaveLength(1);
        expect(visits[0]).toMatchObject({
            method: 'patch',
            url: '/painel/evento/update',
            data: { starts_at: '2026-05-10T12:00:00.000Z', registration_closes_at: null, ends_at: null, status: 'published' },
        });
    });

    it('troca a fase do evento', async () => {
        montar();
        await userEvent.selectOptions(screen.getByLabelText('Fase'), 'draft');
        await userEvent.click(screen.getByRole('button', { name: 'Salvar alterações' }));

        expect(visits[0].data).toMatchObject({ status: 'draft' });
    });

    it('cor de destaque do certificado: o seletor e o texto andam juntos', async () => {
        montar();
        const texto = screen.getByLabelText('Cor de destaque', { selector: '#certificate_accent_color' }) as HTMLInputElement;

        await userEvent.type(texto, '#112233');
        await userEvent.click(screen.getByRole('button', { name: 'Salvar alterações' }));

        expect(visits[0].data).toMatchObject({ certificate_accent_color: '#112233' });
    });

    it('inclui os painéis de regulamento e de logo do certificado', () => {
        montar({ regulamento: { nome_arquivo: 'edital.pdf', atualizado_em: '01/05/2026' }, certificado_logo: { tem_logo: true } });

        expect(screen.getByText('edital.pdf')).toBeTruthy();
        expect(screen.getByText('Logo enviado')).toBeTruthy();
    });

    it('mostra erro da assinatura do certificado ligado ao campo', () => {
        formErrors.certificate_signer_name = 'Nome muito longo.';
        montar();

        expect(screen.getAllByText('Nome muito longo.').length).toBeGreaterThan(0);
        expect(screen.getByLabelText('Nome de quem assina').getAttribute('aria-describedby')).toBe('certificate_signer_name-erro');
    });
});
