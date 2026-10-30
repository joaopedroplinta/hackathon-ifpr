import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { formErrors, setPage, visits } from '@/test/inertia';
import { type PassoTrilha } from '@/types/dashboard';

import MeusCertificados from './certificados/index';
import MostrarCredencial from './credencial/mostrar';
import Dashboard from './dashboard';
import CriarEquipe from './equipe/criar';
import EntrarNaEquipe from './equipe/entrar';
import SemEquipe from './equipe/sem-equipe';
import CriarInscricao from './inscricao/criar';

const perfilOk = { pendente: false, campos: [] };
const passo = (chave: string, status: PassoTrilha['status'], href: string | null = '/x'): PassoTrilha => ({
    chave,
    titulo: `Passo ${chave}`,
    descricao: `Descrição ${chave}`,
    status,
    href,
});

describe('Dashboard', () => {
    it('participante vê a jornada, o progresso e o próximo passo', () => {
        render(
            <Dashboard
                perfil_certificado={perfilOk}
                trilha={[passo('conta', 'concluido', null), passo('inscricao', 'disponivel', '/inscricao'), passo('equipe', 'bloqueado', null)]}
            />,
        );

        expect(screen.getByRole('heading', { level: 1, name: 'Olá, Maria.' })).toBeTruthy();
        expect(screen.getByText('1/3')).toBeTruthy();
        expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('1');
        expect(screen.getByText('Seu próximo passo')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Continuar/ }).getAttribute('href')).toBe('/inscricao');
    });

    it('sem passo disponível, aponta para a programação', () => {
        render(<Dashboard perfil_certificado={perfilOk} trilha={[passo('conta', 'concluido', null)]} />);

        expect(screen.getByText('Acompanhe o evento')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Ver programação/ }).getAttribute('href')).toBe('/agenda/index');
    });

    it('sem evento aberto avisa o participante', () => {
        render(<Dashboard perfil_certificado={perfilOk} trilha={null} />);
        expect(screen.getByText('Nenhum evento aberto')).toBeTruthy();
    });

    it('pede para completar o perfil quando faltam dados do certificado', () => {
        render(<Dashboard perfil_certificado={{ pendente: true, campos: ['CPF', 'vínculo'] }} trilha={null} />);

        expect(screen.getByText('Complete seu perfil para o certificado')).toBeTruthy();
        expect(screen.getByText(/Faltam CPF e vínculo/)).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Completar perfil' }).getAttribute('href')).toBe('/profile/edit');
    });

    it('jurado vê o atalho de avaliações e o painel dedicado, sem jornada', () => {
        setPage({ auth: { is_judge: true, is_participante: false } });
        render(<Dashboard perfil_certificado={perfilOk} trilha={null} />);

        expect(screen.getByText('Participação e avaliação')).toBeTruthy();
        expect(screen.getByText('Suas avaliações')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Minhas avaliações/ }).getAttribute('href')).toBe('/jurado/index');
        expect(screen.queryByText('Nenhum evento aberto')).toBeNull();
    });

    it('organizador vê a central de organização; admin muda o rótulo', () => {
        setPage({ auth: { is_staff: true, is_participante: false } });
        const { unmount } = render(<Dashboard perfil_certificado={perfilOk} trilha={null} />);
        expect(screen.getByText('Central de organização')).toBeTruthy();
        expect(screen.getByText('Organização do evento')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Organizar evento/ }).getAttribute('href')).toBe('/painel/dashboard');
        unmount();

        setPage({ auth: { is_staff: true, is_admin: true, is_participante: false } });
        render(<Dashboard perfil_certificado={perfilOk} trilha={null} />);
        expect(screen.getByText('Administração do evento')).toBeTruthy();
    });

    it('staff que também é jurado combina os rótulos', () => {
        setPage({ auth: { is_staff: true, is_judge: true } });
        render(<Dashboard perfil_certificado={perfilOk} trilha={null} />);
        expect(screen.getByText('Participação, avaliação e organização')).toBeTruthy();
    });
});

describe('MostrarCredencial', () => {
    it('mostra o QR, o nome, o papel e o código de reserva', () => {
        render(<MostrarCredencial nome="Ana Lima" qr_svg="<svg data-qr='1'></svg>" token="abc-123" papel="Participante" />);

        expect(screen.getByRole('heading', { name: 'Seu crachá digital' })).toBeTruthy();
        expect(screen.getByText('Ana Lima')).toBeTruthy();
        expect(screen.getByText('Participante')).toBeTruthy();
        expect(screen.getByLabelText('QR Code do crachá').querySelector('svg[data-qr]')).not.toBeNull();
        expect(screen.getByText(/abc-123/)).toBeTruthy();
    });
});

describe('MeusCertificados', () => {
    it('sem certificados explica quando eles aparecem', () => {
        render(<MeusCertificados certificados={[]} />);
        expect(screen.getByText('Nenhum certificado ainda.')).toBeTruthy();
    });

    it('pronto baixa o PDF; em geração mostra o aviso', () => {
        render(
            <MeusCertificados
                certificados={[
                    { id: 5, evento: 'Hackathon 2026', tipo_label: 'Participação', pronto: true, emitido_em: '10/05/2026' },
                    { id: 6, evento: 'Hackathon 2026', tipo_label: 'Vencedor', pronto: false, emitido_em: '10/05/2026' },
                ]}
            />,
        );

        expect(screen.getByRole('link', { name: /Baixar PDF/ }).getAttribute('href')).toBe('/certificates/download/5');
        expect(screen.getAllByRole('link', { name: /Baixar PDF/ })).toHaveLength(1);
        expect(screen.getByText('Gerando o PDF…')).toBeTruthy();
    });
});

describe('SemEquipe', () => {
    it('pode criar: oferece criar ou entrar com código', () => {
        render(<SemEquipe pode_criar inscricoes_abertas />);

        expect(screen.getByRole('link', { name: /Criar uma equipe/ }).getAttribute('href')).toBe('/teams/create');
        expect(screen.getByRole('link', { name: /Entrar com um código/ }).getAttribute('href')).toBe('/teams/join/create');
    });

    it('não pode criar com inscrições abertas: pede a inscrição primeiro', () => {
        render(<SemEquipe pode_criar={false} inscricoes_abertas />);

        expect(screen.getByText('Confirme sua inscrição no evento antes de formar uma equipe.')).toBeTruthy();
        expect(screen.queryByRole('link', { name: /Criar uma equipe/ })).toBeNull();
    });

    it('prazo encerrado: orienta procurar a organização', () => {
        render(<SemEquipe pode_criar={false} inscricoes_abertas={false} />);
        expect(screen.getByText(/O prazo para formar equipes já encerrou/)).toBeTruthy();
    });
});

describe('EntrarNaEquipe', () => {
    it('envia o código digitado', async () => {
        render(<EntrarNaEquipe />);
        await userEvent.type(screen.getByLabelText('Código de convite', { selector: 'input' }), 'AS3DYP');
        await userEvent.click(screen.getByRole('button', { name: 'Entrar na equipe' }));

        expect(visits).toEqual([{ method: 'post', url: '/teams/join/store', data: { invite_code: 'AS3DYP' } }]);
    });

    it('mostra o erro de código inválido ligado ao campo', () => {
        formErrors.invite_code = 'Código inválido.';
        render(<EntrarNaEquipe />);

        expect(screen.getAllByText('Código inválido.').length).toBeGreaterThan(0);
        expect(screen.getByLabelText('Código de convite', { selector: 'input' }).getAttribute('aria-describedby')).toBe('invite_code-erro');
    });
});

describe('CriarEquipe', () => {
    it('mostra os limites de tamanho e envia nome e descrição', async () => {
        render(<CriarEquipe trilhas={[]} limites={{ minimo: 2, maximo: 5 }} />);

        expect(screen.getByText(/de 2 a 5\s+pessoas/)).toBeTruthy();
        expect(screen.queryByLabelText('Trilha')).toBeNull();

        await userEvent.type(screen.getByLabelText('Nome da equipe'), 'Os Devs');
        await userEvent.type(screen.getByLabelText('Descrição'), 'Um app');
        await userEvent.click(screen.getByRole('button', { name: 'Criar equipe' }));

        expect(visits).toEqual([{ method: 'post', url: '/teams/store', data: { name: 'Os Devs', description: 'Um app', track_id: '' } }]);
    });

    it('só oferece a trilha quando o evento tem trilhas', () => {
        render(<CriarEquipe trilhas={[{ id: 1, name: 'IA', description: null }]} limites={{ minimo: 2, maximo: 5 }} />);
        expect(screen.getByText('Trilha')).toBeTruthy();
    });

    it('mostra o erro de nome duplicado', () => {
        formErrors.name = 'Já existe uma equipe com este nome.';
        render(<CriarEquipe trilhas={[]} limites={{ minimo: 2, maximo: 5 }} />);
        expect(screen.getAllByText('Já existe uma equipe com este nome.').length).toBeGreaterThan(0);
    });
});

describe('CriarInscricao', () => {
    const tamanhos = [{ value: 'M', label: 'M' }];

    it('envia curso e telefone; campos opcionais do evento ficam escondidos se não coletados', async () => {
        render(<CriarInscricao tamanhos={tamanhos} coletar_camisa={false} coletar_restricoes_alimentares={false} />);

        expect(screen.queryByText('Tamanho da camiseta')).toBeNull();
        expect(screen.queryByLabelText('Restrições alimentares')).toBeNull();

        await userEvent.type(screen.getByLabelText('Curso'), 'ADS');
        await userEvent.type(screen.getByLabelText('Telefone'), '41999999999');
        await userEvent.click(screen.getByRole('button', { name: 'Confirmar inscrição' }));

        expect(visits[0]).toMatchObject({ method: 'post', url: '/registration/store', data: { course: 'ADS', phone: '41999999999' } });
    });

    it('mostra camiseta e restrição alimentar quando o evento coleta', () => {
        render(<CriarInscricao tamanhos={tamanhos} coletar_camisa coletar_restricoes_alimentares />);

        expect(screen.getByText('Tamanho da camiseta')).toBeTruthy();
        expect(screen.getByLabelText('Restrições alimentares')).toBeTruthy();
    });

    it('mostra o nome do evento atual', () => {
        setPage({ evento: { nome: 'Hackathon X', slug: 'x', inscricoes_abertas: true, inscrito: false } });
        render(<CriarInscricao tamanhos={tamanhos} coletar_camisa={false} coletar_restricoes_alimentares={false} />);
        expect(screen.getByText(/Hackathon X\./)).toBeTruthy();
    });
});
