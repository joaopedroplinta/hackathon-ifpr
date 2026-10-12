<?php

namespace App\Actions\Demo;

use App\Actions\Judging\DistributeJudges;
use App\Actions\Results\ComputeResults;
use App\Actions\Submissions\DecideLateSubmission;
use App\Actions\Teams\DisqualifyTeam;
use App\Enums\EvaluationStatus;
use App\Enums\EventStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Enums\TeamMemberStatus;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Checkpoint;
use App\Models\ConflictOfInterest;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Incident;
use App\Models\JudgeAssignment;
use App\Models\PopularVote;
use App\Models\Result;
use App\Models\Rubric;
use App\Models\ScheduleItem;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Activitylog\Models\Activity;

/**
 * Prepara a edição de demonstração num estágio do fluxo, para mostrar o
 * sistema ao cliente: `inicio`, `submissoes`, `avaliacao` ou `resultado`.
 * Cada estágio parte do zero, então dá pra repetir a apresentação.
 *
 * Só em ambiente local, de homologação ou de teste. Mexe numa edição própria
 * (slug fixo, edição 2, que na homologação fica como a "atual"), nunca na edição real, e
 * não apaga usuário: as chaves pra `users` são restrict de propósito, então
 * as contas de demonstração são fixas e reaproveitadas.
 */
class PrepareDemoStage
{
    public const ESTAGIOS = ['inicio', 'submissoes', 'avaliacao', 'resultado'];

    public const DOMINIO = 'demo.ifpr.test';

    private const SLUG = 'hackathon-ifpr-demonstracao';

    /** @var array<int, array{nome: string, projeto: string, resumo: string, trilha: string, forca: float}> */
    private const EQUIPES = [
        ['nome' => 'Rota Segura', 'projeto' => 'Rota Segura: mapa colaborativo de alagamentos', 'resumo' => 'Mapa colaborativo de pontos de alagamento e rotas seguras para Pinhais.', 'trilha' => 'Cidade Inteligente', 'forca' => 9.1],
        ['nome' => 'Horta Viva', 'projeto' => 'Horta Viva: hortas comunitárias nas escolas', 'resumo' => 'Plataforma que conecta hortas comunitárias a escolas da rede municipal.', 'trilha' => 'Educação', 'forca' => 8.4],
        ['nome' => 'Alerta Saúde', 'projeto' => 'Alerta Saúde: avisos de vacinação por mensagem', 'resumo' => 'Aviso de vacinação e de consultas por mensagem para idosos.', 'trilha' => 'Saúde', 'forca' => 7.8],
        ['nome' => 'Aula Aberta', 'projeto' => 'Aula Aberta: reforço gravado por estudantes', 'resumo' => 'Banco de aulas de reforço gravadas por estudantes do IFPR.', 'trilha' => 'Educação', 'forca' => 8.8],
        ['nome' => 'Ônibus em Dia', 'projeto' => 'Ônibus em Dia: atrasos a partir de relatos', 'resumo' => 'Previsão de atraso do transporte coletivo a partir de relatos dos passageiros.', 'trilha' => 'Cidade Inteligente', 'forca' => 7.2],
        ['nome' => 'Fila Zero', 'projeto' => 'Fila Zero: agendamento nas unidades de saúde', 'resumo' => 'Agendamento de atendimento nas unidades de saúde para reduzir filas.', 'trilha' => 'Saúde', 'forca' => 8.0],
        ['nome' => 'Lixo Certo', 'projeto' => 'Lixo Certo: guia de descarte por bairro', 'resumo' => 'Guia de descarte e de coleta seletiva por bairro.', 'trilha' => 'Cidade Inteligente', 'forca' => 6.6],
        ['nome' => 'Mentor Júnior', 'projeto' => 'Mentor Júnior: veteranos mentoram calouros', 'resumo' => 'Mentoria entre estudantes veteranos e calouros do curso técnico.', 'trilha' => 'Educação', 'forca' => 7.5],
    ];

    /** Integrantes por equipe, incluindo o líder. */
    private const TAMANHOS = [3, 3, 2, 3, 2, 3, 2, 2];

    /** @var array<int, string> */
    private const NOMES = [
        'Ana Beatriz Lima', 'Bruno Henrique Souza', 'Carla Fernanda Dias', 'Diego Martins Rocha', 'Eduarda Nunes Prado',
        'Felipe Augusto Reis', 'Gabriela Moreira Costa', 'Henrique Tavares Neto', 'Isabela Cardoso Pires', 'João Vitor Almeida',
        'Karina Duarte Melo', 'Lucas Gabriel Barros', 'Mariana Ribeiro Silva', 'Nicolas Ferraz Campos', 'Olívia Santana Braga',
        'Pedro Henrique Lopes', 'Quésia Monteiro Leal', 'Rafael Oliveira Cunha', 'Sofia Mendes Araújo',
    ];

    /** @var array<int, string> */
    private const JURADOS = ['Profa. Renata Albuquerque', 'Prof. Marcelo Teixeira', 'Profa. Helena Siqueira', 'Prof. André Vasconcelos', 'Profa. Cláudia Meireles'];

    /**
     * @return array{evento: string, estagio: string, equipes: int, submissoes: int, avaliacoes_enviadas: int, contas: array<string, string>}
     */
    public function handle(string $estagio, string $senha): array
    {
        if (! in_array($estagio, self::ESTAGIOS, true)) {
            throw new InvalidArgumentException('Estágio inválido. Use: '.implode(', ', self::ESTAGIOS).'.');
        }

        if (! app()->environment(['local', 'staging', 'testing'])) {
            throw new RuntimeException('A demonstração só roda em ambiente local ou de homologação, nunca em produção.');
        }

        return DB::transaction(function () use ($estagio, $senha) {
            $evento = $this->evento($estagio);
            $contas = $this->contas($senha);

            $this->limpar($evento);
            $trilhas = $this->trilhas($evento);
            $rubrica = $this->rubrica($evento);
            $this->agenda($evento);
            $equipes = $this->equipes($evento, $contas, $trilhas);

            $submissoes = 0;
            $enviadas = 0;

            if ($estagio !== 'inicio') {
                $this->submissoes($evento, $equipes, $contas['participante']);
                $submissoes = Submission::forEvent($evento)->count();
            }

            if (in_array($estagio, ['avaliacao', 'resultado'], true)) {
                $enviadas = $this->avaliar($evento, $rubrica, $contas, $equipes, completo: $estagio === 'resultado');
            }

            if ($estagio === 'resultado') {
                $this->fechar($evento, $contas, $equipes);
            }

            return [
                'evento' => $evento->name,
                'estagio' => $estagio,
                'equipes' => Team::forEvent($evento)->count(),
                'submissoes' => $submissoes,
                'avaliacoes_enviadas' => $enviadas,
                'contas' => [
                    'Organização' => $contas['organizacao']->email,
                    'Jurado' => $contas['jurado']->email,
                    'Participante' => $contas['participante']->email,
                ],
            ];
        });
    }

    private function evento(string $estagio): Event
    {
        $inicio = $estagio === 'inicio';

        $evento = Event::firstOrNew(['slug' => self::SLUG]);
        $evento->forceFill([
            'name' => '1º Hackathon IFPR Pinhais (demonstração)',
            'edition' => 2,
            'status' => $inicio ? EventStatus::Published : EventStatus::Running,
            'description' => 'Edição de demonstração: dados fictícios, para mostrar o fluxo completo do sistema.',
            'registration_opens_at' => $inicio ? now()->subDays(2) : now()->subWeeks(3),
            'registration_closes_at' => $inicio ? now()->addDays(5) : now()->subDays(5),
            'starts_at' => $inicio ? now()->addDays(10) : now()->subDay(),
            'ends_at' => $inicio ? now()->addDays(12) : now()->addDay(),
            'submission_deadline' => $inicio ? now()->addDays(12) : now()->subMinutes(30),
            'voting_opens_at' => $estagio === 'resultado' ? now()->subHour() : null,
            'voting_closes_at' => $estagio === 'resultado' ? now()->addDays(2) : null,
            'results_published_at' => null,
            'min_team_size' => 2,
            'max_team_size' => 5,
            'judges_per_submission' => 3,
        ])->save();

        return $evento->fresh();
    }

    /**
     * Contas fixas, reaproveitadas a cada rodada. Só as três que o cliente usa
     * recebem a senha da demonstração; as demais ficam com senha aleatória
     * que ninguém conhece.
     *
     * @return array{organizacao: User, jurado: User, participante: User, jurados: array<int, User>, participantes: array<int, User>}
     */
    private function contas(string $senha): array
    {
        $conta = function (string $email, string $nome, Role $papel, ?string $senhaConhecida = null): User {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $nome,
                'password' => Hash::make($senhaConhecida ?? Str::random(40)),
                'email_verified_at' => now(),
            ])->save();
            $user->syncRoles([$papel->value]);

            return $user;
        };

        $dominio = self::DOMINIO;
        $organizacao = $conta("organizacao.demo@{$dominio}", 'Organização (demonstração)', Role::Organizador, $senha);
        $jurado = $conta("jurado.demo@{$dominio}", self::JURADOS[0], Role::Jurado, $senha);
        $participante = $conta("participante.demo@{$dominio}", 'Participante (demonstração)', Role::Participante, $senha);

        $jurados = [$jurado];
        foreach (array_slice(self::JURADOS, 1) as $i => $nome) {
            $jurados[] = $conta(sprintf('jurado%d.demo@%s', $i + 2, $dominio), $nome, Role::Jurado);
        }

        $participantes = [];
        foreach (self::NOMES as $i => $nome) {
            $participantes[] = $conta(sprintf('p%02d.demo@%s', $i + 1, $dominio), $nome, Role::Participante);
        }

        return compact('organizacao', 'jurado', 'participante', 'jurados', 'participantes');
    }

    /** Apaga tudo que é da edição de demonstração, na ordem que as chaves pedem. */
    private function limpar(Event $evento): void
    {
        $id = $evento->id;

        PopularVote::where('event_id', $id)->delete();
        Result::where('event_id', $id)->delete();
        Certificate::where('event_id', $id)->delete();

        $checkpoints = Checkpoint::where('event_id', $id)->pluck('id');
        Attendance::whereIn('checkpoint_id', $checkpoints)->delete();
        Checkpoint::where('event_id', $id)->delete();

        $atribuicoes = JudgeAssignment::where('event_id', $id)->pluck('id');
        $avaliacoes = Evaluation::whereIn('assignment_id', $atribuicoes)->pluck('id');
        EvaluationScore::whereIn('evaluation_id', $avaliacoes)->delete();
        Evaluation::whereIn('id', $avaliacoes)->delete();
        JudgeAssignment::whereIn('id', $atribuicoes)->delete();

        $equipes = Team::withTrashed()->where('event_id', $id)->pluck('id');
        ConflictOfInterest::whereIn('team_id', $equipes)->delete();

        $submissoes = Submission::withTrashed()->where('event_id', $id)->pluck('id');
        SubmissionFile::withTrashed()->whereIn('submission_id', $submissoes)->forceDelete();
        SubmissionVersion::whereIn('submission_id', $submissoes)->delete();
        Submission::withTrashed()->whereIn('id', $submissoes)->forceDelete();

        TeamInvite::whereIn('team_id', $equipes)->delete();
        TeamMember::whereIn('team_id', $equipes)->delete();
        Team::withTrashed()->whereIn('id', $equipes)->forceDelete();

        EventRegistration::where('event_id', $id)->delete();
        Incident::where('event_id', $id)->delete();
        ScheduleItem::where('event_id', $id)->delete();
        Rubric::where('event_id', $id)->delete();

        // Rastro de auditoria das equipes e submissões que acabaram de sumir.
        Activity::query()
            ->where(fn ($q) => $q->where('subject_type', Team::class)->whereIn('subject_id', $equipes))
            ->orWhere(fn ($q) => $q->where('subject_type', Submission::class)->whereIn('subject_id', $submissoes))
            ->delete();
    }

    /**
     * @return array<string, Track>
     */
    private function trilhas(Event $evento): array
    {
        $trilhas = [];

        foreach (['Educação', 'Saúde', 'Cidade Inteligente'] as $nome) {
            // Atribuição explícita: event_id não está no $fillable de Track, e
            // fora de um seeder o firstOrCreate o descartaria em silêncio.
            $trilha = Track::where('event_id', $evento->id)->where('name', $nome)->first() ?? new Track;
            $trilha->event_id = $evento->id;
            $trilha->name = $nome;
            $trilha->description = "Soluções para {$nome}.";
            $trilha->color = '#2563eb';
            $trilha->save();

            $trilhas[$nome] = $trilha;
        }

        return $trilhas;
    }

    private function rubrica(Event $evento): Rubric
    {
        $rubrica = Rubric::factory()->for($evento)->ativa()->create(['name' => 'Rubrica oficial']);

        foreach ([
            ['name' => 'Inovação', 'weight' => '3.00', 'position' => 1, 'description' => 'Originalidade da solução e criatividade na abordagem do problema.'],
            ['name' => 'Execução técnica', 'weight' => '3.00', 'position' => 2, 'description' => 'Qualidade do código, arquitetura e funcionamento da demonstração.'],
            ['name' => 'Impacto', 'weight' => '2.00', 'position' => 3, 'description' => 'Relevância da solução para o problema e potencial de uso real.'],
            ['name' => 'Apresentação', 'weight' => '1.00', 'position' => 4, 'description' => 'Clareza do pitch, domínio do projeto e uso do tempo.'],
        ] as $criterio) {
            Criterion::factory()->for($rubrica)->create($criterio + ['max_score' => 10]);
        }

        return $rubrica->load('criteria');
    }

    /** Hora cheia no horário de Brasília do dia do evento, de volta em UTC para gravar. */
    private function horaLocal(Carbon $dia, int $hora): Carbon
    {
        return $dia->copy()->timezone('America/Sao_Paulo')->setTime($hora, 0)->utc();
    }

    private function agenda(Event $evento): void
    {
        $abertura = $this->horaLocal($evento->starts_at, 9);

        foreach ([
            ['Abertura e boas-vindas', 'palestra', 0, 1, 'Auditório', 'Apresentação do desafio e das regras do evento.'],
            ['Oficina de prototipação rápida', 'workshop', 1, 2, 'Laboratório 2', 'Como transformar a ideia em um protótipo em poucas horas.'],
            ['Almoço', 'refeicao', 3, 1, 'Refeitório', 'Intervalo para o almoço.'],
            ['Prazo final de submissão', 'deadline', 8, 0, null, 'Envio dos projetos pelo sistema.'],
            ['Pitches e avaliação da banca', 'palestra', 9, 3, 'Auditório', 'Cada equipe apresenta o projeto para a banca de jurados.'],
        ] as [$titulo, $tipo, $horasDepois, $duracao, $local, $descricao]) {
            ScheduleItem::factory()->for($evento)->create([
                'title' => $titulo,
                'type' => $tipo,
                'description' => $descricao,
                'location' => $local,
                'starts_at' => $abertura->copy()->addHours($horasDepois),
                'ends_at' => $abertura->copy()->addHours($horasDepois + max($duracao, 1)),
                'is_published' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $contas
     * @param  array<string, Track>  $trilhas
     * @return array<int, Team>
     */
    private function equipes(Event $evento, array $contas, array $trilhas): array
    {
        $fila = $contas['participantes'];
        $equipes = [];

        foreach (self::EQUIPES as $i => $dados) {
            $lider = $i === 0 ? $contas['participante'] : array_shift($fila);

            $equipe = Team::factory()->for($evento)->confirmada()->create([
                'name' => $dados['nome'],
                'slug' => Str::slug($dados['nome']),
                'leader_id' => $lider->id,
                'track_id' => $trilhas[$dados['trilha']]->id,
                'description' => $dados['resumo'],
            ]);

            $integrantes = [$lider];
            for ($n = 1; $n < self::TAMANHOS[$i]; $n++) {
                $integrantes[] = array_shift($fila);
            }

            foreach ($integrantes as $pos => $user) {
                EventRegistration::factory()->for($evento)->for($user)->create();

                $membro = TeamMember::factory()->for($evento)->for($equipe)->for($user);
                ($pos === 0 ? $membro->lider() : $membro)->create(['status' => TeamMemberStatus::Active]);
            }

            $equipes[] = $equipe;
        }

        return $equipes;
    }

    /**
     * Seis no prazo, uma atrasada aguardando decisão e uma equipe sem envio
     * (a última), para o organizador ter o que decidir e cobrar.
     *
     * @param  array<int, Team>  $equipes
     */
    private function submissoes(Event $evento, array $equipes, User $autorPadrao): void
    {
        $prazo = $evento->submission_deadline;

        foreach ($equipes as $i => $equipe) {
            if ($i === 7) {
                continue;
            }

            $dados = self::EQUIPES[$i];
            $atrasada = $i === 6;

            $fabrica = Submission::factory()->for($evento)->for($equipe);
            $submissao = ($atrasada ? $fabrica->atrasada() : $fabrica->enviada())->create([
                'title' => $dados['projeto'],
                'summary' => $dados['resumo'],
                'description' => $dados['resumo'].' Projeto de demonstração, com dados fictícios.',
                'repo_url' => 'https://github.com/demo-ifpr/'.Str::slug($dados['nome']),
                'submitted_at' => $atrasada ? $prazo->copy()->addMinutes(12) : $prazo->copy()->subHours(2 + $i),
            ]);

            $versao = new SubmissionVersion;
            $versao->submission_id = $submissao->id;
            $versao->version = 1;
            $versao->created_by = $equipe->leader_id;
            $versao->payload = [
                'title' => $submissao->title,
                'summary' => $submissao->summary,
                'repo_url' => $submissao->repo_url,
                'status' => $submissao->status->value,
                'source' => 'web',
                'submitted_at' => $submissao->submitted_at->toIso8601String(),
                'files' => [],
            ];
            $versao->save();
        }
    }

    /**
     * Distribui os jurados e lança avaliações. No estágio de avaliação, o
     * jurado de demonstração fica com tudo pendente (é ele que o cliente
     * usa) e os outros entregam a maior parte; no de resultado, todos entregam.
     *
     * @param  array<string, mixed>  $contas
     * @param  array<int, Team>  $equipes
     */
    private function avaliar(Event $evento, Rubric $rubrica, array $contas, array $equipes, bool $completo): int
    {
        $atrasada = Submission::forEvent($evento)->where('team_id', $equipes[6]->id)->firstOrFail();
        app(DecideLateSubmission::class)->handle($atrasada, true, 'Queda de rede do campus durante a entrega, comprovada pelo chamado da TI.', $contas['organizacao']);

        app(DistributeJudges::class)->handle($evento->fresh());

        $forca = [];
        foreach ($equipes as $i => $equipe) {
            $forca[$equipe->id] = self::EQUIPES[$i]['forca'];
        }

        $enviadas = 0;

        foreach (JudgeAssignment::forEvent($evento)->with('submission')->orderBy('id')->get() as $n => $atribuicao) {
            $ehDoJuradoDemo = $atribuicao->judge_id === $contas['jurado']->id;

            if (! $completo && ($ehDoJuradoDemo || $n % 5 === 4)) {
                continue;
            }

            $base = $forca[$atribuicao->submission->team_id] ?? 7.0;

            $avaliacao = new Evaluation;
            $avaliacao->assignment_id = $atribuicao->id;
            $avaliacao->status = EvaluationStatus::Draft;
            $avaliacao->save();

            foreach ($rubrica->criteria as $c => $criterio) {
                $variacao = ((($n * 7) + ($c * 3)) % 9) / 10 - 0.4;

                $nota = new EvaluationScore;
                $nota->evaluation_id = $avaliacao->id;
                $nota->criterion_id = $criterio->id;
                $nota->score = number_format(min(10, max(3, $base + $variacao)), 2, '.', '');
                $nota->save();
            }

            $avaliacao->status = EvaluationStatus::Submitted;
            $avaliacao->submitted_at = now()->subMinutes(20 + $n);
            $avaliacao->save();

            $atribuicao->status = JudgeAssignmentStatus::Done;
            $atribuicao->save();

            $enviadas++;
        }

        return $enviadas;
    }

    /**
     * Estágio final: uma equipe desclassificada (para mostrar o efeito),
     * resultado calculado e ainda NÃO publicado, e presenças registradas
     * para dar para emitir os certificados ao vivo.
     *
     * @param  array<string, mixed>  $contas
     * @param  array<int, Team>  $equipes
     */
    private function fechar(Event $evento, array $contas, array $equipes): void
    {
        // Uma equipe com projeto enviado, para o efeito aparecer no ranking.
        app(DisqualifyTeam::class)->handle(
            $equipes[5]->fresh(),
            'Projeto copiado de repositório público sem declarar a origem, conforme o regulamento.',
            $contas['organizacao'],
            notificar: false,
        );

        app(ComputeResults::class)->handle($evento);

        $entrada = Checkpoint::factory()->for($evento)->create([
            'name' => 'Entrada',
            'starts_at' => $this->horaLocal($evento->starts_at, 8),
            'ends_at' => $this->horaLocal($evento->starts_at, 10),
        ]);

        $presentes = TeamMember::where('event_id', $evento->id)->where('status', TeamMemberStatus::Active)->pluck('user_id');

        foreach ($presentes as $userId) {
            Attendance::factory()->for($entrada)->create([
                'user_id' => $userId,
                'checked_by' => $contas['organizacao']->id,
                'checked_in_at' => Carbon::parse($entrada->starts_at)->addMinutes(15),
            ]);
        }
    }
}
