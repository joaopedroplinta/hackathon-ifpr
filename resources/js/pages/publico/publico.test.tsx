import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

import { setPage, visits } from '@/test/inertia';
import { type EventoPublico, type ItemAgenda } from '@/types/publico';

import Agenda from './agenda';
import Cookies from './cookies';
import Edicoes from './edicoes';
import Inicio from './inicio';
import Privacidade from './privacidade';
import Projetos from './projetos';
import Regulamento from './regulamento';
import Resultados from './resultados';
import Rubrica from './rubrica';
import ValidarCertificado from './validar';

const h1 = (nome: string | RegExp) => screen.getByRole('heading', { level: 1, name: nome });

const item = (id: number, extra: Partial<ItemAgenda> = {}): ItemAgenda => ({
    id,
    titulo: `Atividade ${id}`,
    descricao: null,
    tipo: 'palestra',
    tipo_label: 'Palestra',
    destaque: false,
    inicia_em: '2026-05-10T15:00:00Z',
    termina_em: '2026-05-10T16:00:00Z',
    local: null,
    palestrante: null,
    trilha: null,
    ...extra,
});

describe('Agenda', () => {
    it('sem itens mostra o estado vazio e esconde o .ics', () => {
        render(<Agenda evento={null} itens={[]} />);

        expect(screen.getByText('Agenda ainda não publicada')).toBeTruthy();
        expect(screen.queryByRole('link', { name: /\.ics/ })).toBeNull();
    });

    it('mostra horário de Brasília, local, palestrante e o link do calendário', () => {
        render(
            <Agenda
                evento={{ nome: 'Hackathon 2026' }}
                itens={[item(1, { local: 'Auditório', palestrante: 'Dra. Silva', descricao: 'Abertura', trilha: { nome: 'IA', cor: null } })]}
            />,
        );

        expect(screen.getByText('12:00')).toBeTruthy();
        expect(screen.getByText('13:00')).toBeTruthy();
        expect(screen.getByText('Auditório')).toBeTruthy();
        expect(screen.getByText('Dra. Silva')).toBeTruthy();
        expect(screen.getByText('IA')).toBeTruthy();
        expect(screen.getByRole('link', { name: /\.ics/ }).getAttribute('href')).toBe('/agenda/ics');
        expect(screen.getByRole('status').textContent).toBe('1 atividade');
    });

    it('filtra por tipo e limpa os filtros', async () => {
        render(<Agenda evento={null} itens={[item(1), item(2, { tipo: 'workshop', tipo_label: 'Oficina' })]} />);
        expect(screen.getByRole('status').textContent).toBe('2 atividades');

        await userEvent.selectOptions(screen.getByLabelText('Atividade'), 'workshop');
        expect(screen.getByRole('status').textContent).toBe('1 atividade');
        expect(screen.queryByText('Atividade 1')).toBeNull();

        await userEvent.click(screen.getByRole('button', { name: 'Limpar filtros' }));
        expect(screen.getByRole('status').textContent).toBe('2 atividades');
    });

    it('filtra por dia e avisa quando a combinação não tem nada', async () => {
        render(
            <Agenda
                evento={null}
                itens={[
                    item(1),
                    item(2, { tipo: 'workshop', tipo_label: 'Oficina', inicia_em: '2026-05-11T15:00:00Z', termina_em: '2026-05-11T16:00:00Z' }),
                ]}
            />,
        );

        await userEvent.selectOptions(screen.getByLabelText('Dia'), '2026-05-10');
        await userEvent.selectOptions(screen.getByLabelText('Atividade'), 'workshop');

        expect(screen.getByText('Nenhuma atividade com estes filtros')).toBeTruthy();
    });

    it('marca "Acontecendo agora" só para o item em andamento', () => {
        const agora = Date.now();
        render(
            <Agenda
                evento={null}
                itens={[
                    item(1, { inicia_em: new Date(agora - 3600_000).toISOString(), termina_em: new Date(agora + 3600_000).toISOString() }),
                    item(2, { inicia_em: new Date(agora + 7200_000).toISOString(), termina_em: new Date(agora + 9000_000).toISOString() }),
                ]}
            />,
        );

        expect(screen.getAllByText('Acontecendo agora')).toHaveLength(1);
    });
});

describe('Páginas informativas', () => {
    it('cookies e privacidade têm índice e linkam uma para a outra', () => {
        const { unmount } = render(<Cookies />);
        expect(h1('Cookies')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'política de privacidade' }).getAttribute('href')).toBe('/privacidade/show');
        unmount();

        render(<Privacidade />);
        expect(h1('Política de Privacidade')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'política de cookies' }).getAttribute('href')).toBe('/cookies/show');
        expect(screen.getByRole('link', { name: 'hackathon@ifpr.edu.br' }).getAttribute('href')).toBe('mailto:hackathon@ifpr.edu.br');
    });
});

describe('Edicoes', () => {
    it('sem edições encerradas, orienta a voltar ao evento atual', () => {
        render(<Edicoes edicoes={[]} />);
        expect(screen.getByText('Nenhuma edição encerrada ainda')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Ver evento atual' })).toBeTruthy();
    });

    it('lista as edições com número, data de encerramento e link', () => {
        render(
            <Edicoes
                edicoes={[
                    { nome: 'Hackathon 2025', edicao: 1, slug: 'h-2025', encerrado_em: '2025-11-20T15:00:00Z' },
                    { nome: 'Hackathon 2024', edicao: 2, slug: 'h-2024', encerrado_em: null },
                ]}
            />,
        );

        expect(screen.getByText('01')).toBeTruthy();
        expect(screen.getByText('Encerrada em 20 de novembro de 2025')).toBeTruthy();
        expect(screen.getByText('Edição encerrada')).toBeTruthy();
        expect(screen.getByRole('link', { name: /Hackathon 2025/ }).getAttribute('href')).toBe('/resultados/show/edicao/h-2025');
    });
});

describe('Inicio', () => {
    const evento = (extra: Partial<EventoPublico> = {}): EventoPublico => ({
        nome: 'Hackathon IFPR 2026',
        descricao: null,
        edicao: 1,
        situacao: 'published',
        situacao_label: 'Publicado',
        inicia_em: null,
        termina_em: null,
        inscricoes_abrem_em: null,
        inscricoes_fecham_em: null,
        inscricoes_abertas: true,
        ...extra,
    });
    const stats = { inscritos: 42, equipes: 10, trilhas: 0 };
    const cta = (nome: string) => screen.getByRole('link', { name: new RegExp(nome) });

    it('sem evento publicado, convida a ver as edições anteriores', () => {
        render(<Inicio evento={null} estatisticas={null} />);
        expect(screen.getByText(/Nenhum evento publicado/)).toBeTruthy();
        expect(screen.getByRole('link', { name: /Explorar edições anteriores/ })).toBeTruthy();
    });

    it('visitante com inscrições abertas vai para o cadastro', () => {
        setPage({ auth: { user: null } });
        render(<Inicio evento={evento()} estatisticas={stats} />);

        expect(h1('Hackathon IFPR 2026')).toBeTruthy();
        expect(cta('Quero participar').getAttribute('href')).toBe('/register');
        expect(screen.getByText('Inscrições abertas. Crie sua conta para começar.')).toBeTruthy();
    });

    it('visitante com inscrições fechadas vai para a programação', () => {
        setPage({ auth: { user: null } });
        render(<Inicio evento={evento({ inscricoes_abertas: false })} estatisticas={null} />);

        expect(cta('Explorar a programação').getAttribute('href')).toBe('/agenda/index');
        expect(screen.getByText('As inscrições não estão abertas no momento.')).toBeTruthy();
    });

    it('logado e não inscrito é convidado a se inscrever', () => {
        render(<Inicio evento={evento()} estatisticas={null} />);
        expect(cta('Fazer minha inscrição').getAttribute('href')).toBe('/registration/create');
    });

    it('já inscrito vai para o painel e vê a confirmação', () => {
        setPage({ evento: { nome: 'x', slug: 'x', inscricoes_abertas: true, inscrito: true } });
        render(<Inicio evento={evento()} estatisticas={null} />);

        expect(cta('Acessar meu painel').getAttribute('href')).toBe('/dashboard');
        expect(screen.getByText(/Sua inscrição está confirmada/)).toBeTruthy();
    });

    it('mostra a mensagem certa para evento em andamento e encerrado', () => {
        const { unmount } = render(<Inicio evento={evento({ situacao: 'running', inscricoes_abertas: false })} estatisticas={null} />);
        expect(screen.getByText('O hackathon está acontecendo. Acompanhe a agenda.')).toBeTruthy();
        unmount();

        render(<Inicio evento={evento({ situacao: 'finished', inscricoes_abertas: false })} estatisticas={null} />);
        expect(screen.getByText('Esta edição terminou. Consulte a página de resultados.')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Consultar resultados' })).toBeTruthy();
    });

    it('mostra números do evento e só inclui trilhas quando existem', () => {
        const { unmount } = render(<Inicio evento={evento()} estatisticas={stats} />);
        expect(screen.getByText('pessoas inscritas')).toBeTruthy();
        expect(screen.getByText('equipes formadas')).toBeTruthy();
        expect(screen.queryByText('trilhas para explorar')).toBeNull();
        unmount();

        render(<Inicio evento={evento()} estatisticas={{ ...stats, trilhas: 3 }} />);
        expect(screen.getByText('trilhas para explorar')).toBeTruthy();
    });

    it('mostra a contagem regressiva só antes do início', () => {
        const futuro = new Date(Date.now() + 5 * 86400_000).toISOString();
        render(<Inicio evento={evento({ inicia_em: futuro })} estatisticas={null} />);
        expect(screen.getByRole('timer', { name: 'Faltam para o início' })).toBeTruthy();
    });
});

describe('Projetos', () => {
    const submissoes = [
        { id: 1, titulo: 'App de Rotas', resumo: 'Planeja trajetos', equipe: 'Equipe Alfa' },
        { id: 2, titulo: 'Horta Digital', resumo: null, equipe: 'Equipe Beta' },
    ];
    const props = { evento: { nome: 'Hackathon' }, submissoes, votacao_aberta: true, pode_votar: true, ja_votou_em: null };

    it('sem projetos mostra o estado vazio', () => {
        render(<Projetos {...props} submissoes={[]} />);
        expect(screen.getByText('Nenhum projeto enviado ainda')).toBeTruthy();
        expect(screen.queryByLabelText('Buscar projeto ou equipe')).toBeNull();
    });

    it('busca por título, equipe ou resumo e permite limpar', async () => {
        render(<Projetos {...props} />);
        expect(screen.getByRole('status').textContent).toBe('2 de 2 projetos');

        await userEvent.type(screen.getByLabelText('Buscar projeto ou equipe'), 'beta');
        expect(screen.getByRole('status').textContent).toBe('1 de 2 projetos');
        expect(screen.queryByText('App de Rotas')).toBeNull();

        await userEvent.clear(screen.getByLabelText('Buscar projeto ou equipe'));
        await userEvent.type(screen.getByLabelText('Buscar projeto ou equipe'), 'inexistente');
        expect(screen.getByText('Nenhum projeto encontrado')).toBeTruthy();

        await userEvent.click(screen.getByRole('button', { name: 'Limpar busca' }));
        expect(screen.getByRole('status').textContent).toBe('2 de 2 projetos');
    });

    it('vota só depois de confirmar no diálogo', async () => {
        render(<Projetos {...props} />);

        await userEvent.click(screen.getByRole('button', { name: 'Votar em App de Rotas' }));
        const dialogo = screen.getByRole('dialog', { name: 'Confirmar seu voto?' });
        expect(within(dialogo).getByText('App de Rotas')).toBeTruthy();
        expect(visits).toHaveLength(0);

        await userEvent.click(within(dialogo).getByRole('button', { name: 'Confirmar voto' }));
        expect(visits).toEqual([{ method: 'post', url: '/votos/store', data: { submission_id: 1 } }]);
    });

    it('voltar fecha o diálogo sem votar', async () => {
        render(<Projetos {...props} />);
        await userEvent.click(screen.getByRole('button', { name: 'Votar em Horta Digital' }));
        await userEvent.click(screen.getByRole('button', { name: 'Voltar aos projetos' }));

        expect(screen.queryByRole('dialog')).toBeNull();
        expect(visits).toHaveLength(0);
    });

    it.each([
        ['já votou', { ja_votou_em: 1 }, 'Seu voto foi registrado.'],
        ['votação fechada', { votacao_aberta: false }, 'A votação popular não está aberta no momento.'],
        ['não inscrito', { pode_votar: false }, 'Você precisa estar inscrito neste evento para votar.'],
    ])('%s: explica e esconde os botões de voto', (_nome, extra, mensagem) => {
        render(<Projetos {...props} {...extra} />);

        expect(screen.getByText(new RegExp(mensagem))).toBeTruthy();
        expect(screen.queryByRole('button', { name: /Votar em/ })).toBeNull();
    });

    it('marca o projeto em que a pessoa votou', () => {
        render(<Projetos {...props} ja_votou_em={2} />);
        expect(screen.getByText('Seu voto')).toBeTruthy();
    });

    it('visitante é convidado a entrar', () => {
        setPage({ auth: { user: null } });
        render(<Projetos {...props} pode_votar={false} />);

        expect(screen.getByText('Entre na sua conta e inscreva-se no evento para votar.')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Entrar para participar' }).getAttribute('href')).toBe('/login');
    });
});

describe('Regulamento', () => {
    const arquivo = { tem_arquivo: false, nome_arquivo: null, atualizado_em: null };

    it('mostra limites de equipe e prazo do evento', () => {
        render(
            <Regulamento evento={{ nome: 'H', min_team_size: 2, max_team_size: 5, submission_deadline: '10/05/2026 18:00' }} regulamento={arquivo} />,
        );

        expect(screen.getByText('Equipes de 2 a 5 pessoas')).toBeTruthy();
        expect(screen.getByText('Prazo de submissão: 10/05/2026 18:00')).toBeTruthy();
        expect(screen.queryByRole('link', { name: /Baixar PDF/ })).toBeNull();
        expect(screen.getByText(/quando publicado/)).toBeTruthy();
    });

    it('com edital anexado oferece o download', () => {
        render(<Regulamento evento={null} regulamento={{ tem_arquivo: true, nome_arquivo: 'e.pdf', atualizado_em: '01/05/2026' }} />);

        expect(screen.getByRole('link', { name: /Baixar PDF do edital/ }).getAttribute('href')).toBe('/regulamento/download');
        expect(screen.getByText('Atualizado em 01/05/2026')).toBeTruthy();
        expect(screen.getByText('Nenhum evento em cartaz no momento.')).toBeTruthy();
    });
});

describe('Resultados', () => {
    const podio = [
        { posicao: 2, titulo: 'Segundo', equipe: 'B', nota_final: 8.5, trilha: null },
        { posicao: 1, titulo: 'Primeiro', equipe: 'A', nota_final: 9.25, trilha: 'IA' },
    ];

    it('não publicado: esconde o ranking', () => {
        render(<Resultados publicado={false} evento={null} podio_geral={[]} podio_por_trilha={{}} premio_popular={null} />);
        expect(screen.getByText('Resultado ainda não publicado')).toBeTruthy();
        expect(screen.queryByText('Pódio geral')).toBeNull();
    });

    it('publicado: ordena por posição e formata a nota em pt-BR', () => {
        render(<Resultados publicado evento={{ nome: 'Hackathon', edicao: 1 }} podio_geral={podio} podio_por_trilha={{}} premio_popular={null} />);

        const itens = screen.getAllByRole('listitem').filter((li) => li.textContent?.includes('Nota final'));
        expect(itens[0].textContent).toContain('Primeiro');
        expect(itens[0].textContent).toContain('9,25');
        expect(itens[1].textContent).toContain('Segundo');
        expect(screen.getByText('Hackathon · Edição 1')).toBeTruthy();
    });

    it('mostra pódio por trilha e prêmio popular (singular/plural)', () => {
        const { unmount } = render(
            <Resultados
                publicado
                evento={null}
                podio_geral={podio}
                podio_por_trilha={{ IA: [podio[1]] }}
                premio_popular={{ titulo: 'Horta', equipe: 'C', votos: 1 }}
            />,
        );
        expect(screen.getByText('Pódio por trilha')).toBeTruthy();
        expect(screen.getByText('C · 1 voto')).toBeTruthy();
        unmount();

        render(
            <Resultados publicado evento={null} podio_geral={[]} podio_por_trilha={{}} premio_popular={{ titulo: 'Horta', equipe: 'C', votos: 7 }} />,
        );
        expect(screen.getByText('Nenhuma submissão pontuada ainda.')).toBeTruthy();
        expect(screen.getByText('C · 7 votos')).toBeTruthy();
    });
});

describe('Rubrica', () => {
    it('sem critérios mostra o estado vazio', () => {
        render(<Rubrica evento={null} criterios={[]} />);
        expect(screen.getByText('Rubrica ainda não publicada')).toBeTruthy();
    });

    it('calcula a participação de cada peso e a soma', () => {
        render(
            <Rubrica
                evento={{ nome: 'H' }}
                criterios={[
                    { id: 1, nome: 'Inovação', descricao: 'Originalidade', peso: 3, nota_maxima: 10 },
                    { id: 2, nome: 'Execução', descricao: null, peso: 1, nota_maxima: 10 },
                ]}
            />,
        );

        expect(screen.getByText('75%')).toBeTruthy();
        expect(screen.getByText('25%')).toBeTruthy();
        expect(screen.getByText('Originalidade')).toBeTruthy();
        expect(screen.getByText('Soma dos pesos:').textContent).toContain('4');
    });
});

describe('ValidarCertificado', () => {
    it('código inexistente', () => {
        render(<ValidarCertificado encontrado={false} />);
        expect(screen.getByText('Certificado não encontrado')).toBeTruthy();
    });

    it('certificado revogado não vale', () => {
        render(<ValidarCertificado encontrado={false} revogado />);
        expect(screen.getByText('Certificado revogado')).toBeTruthy();
        expect(screen.queryByText('Certificado válido')).toBeNull();
    });

    it('certificado válido mostra os dados emitidos', () => {
        render(
            <ValidarCertificado
                encontrado
                nome="Ana Lima"
                tipo_label="Participação"
                evento="Hackathon 2026"
                carga_horaria={8}
                emitido_em="10/05/2026"
            />,
        );

        expect(screen.getByText('Certificado válido')).toBeTruthy();
        expect(screen.getByRole('heading', { name: 'Ana Lima' })).toBeTruthy();
        expect(screen.getByText('8 horas')).toBeTruthy();
        expect(screen.getByText('Participação')).toBeTruthy();
    });
});
