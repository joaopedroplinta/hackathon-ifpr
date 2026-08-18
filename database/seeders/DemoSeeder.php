<?php

namespace Database\Seeders;

use App\Actions\Judging\DistributeJudges;
use App\Actions\Results\ComputeResults;
use App\Enums\EvaluationStatus;
use App\Enums\EventStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Enums\TeamMemberStatus;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\JudgeAssignment;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Popula a demo com dois eventos pra mostrar o sistema em duas fases: uma
 * edição encerrada (resultado publicado, histórico pra `Resultados`) e a
 * edição atual (inscrições abertas, equipes se formando, nada julgado ainda).
 *
 * Edição do evento passado fica abaixo da do `DatabaseSeeder` de propósito --
 * `Event::current()` ordena por edição desc, e a edição em andamento tem que
 * continuar sendo a que aparece como atual em todo o sistema.
 *
 * Idempotente como os outros seeders: completa até o total, não duplica se
 * rodar de novo (`migrate:fresh` está negado).
 */
class DemoSeeder extends Seeder
{
    private const EQUIPES_EVENTO_PASSADO = 15;

    private const EQUIPES_EVENTO_ATUAL = 8;

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $eventoAtual = Event::current();

        if (! $eventoAtual) {
            $this->command?->error('Nenhum evento publicado. Rode o DatabaseSeeder primeiro.');

            return;
        }

        $eventoPassado = $this->ensureEventoPassado();

        $this->popularEquipesEInscricoes($eventoPassado, self::EQUIPES_EVENTO_PASSADO, comSubmissao: true);
        $rubrica = $this->ensureRubricaAtiva($eventoPassado);
        $this->ensureAvaliacoesCompletas($eventoPassado, $rubrica);
        app(ComputeResults::class)->handle($eventoPassado);

        if (! $eventoPassado->results_published_at) {
            $eventoPassado->update(['results_published_at' => now()->subWeek()]);
        }

        $this->popularEquipesEInscricoes($eventoAtual, self::EQUIPES_EVENTO_ATUAL, comSubmissao: false);

        $this->command?->info(
            "Evento passado ({$eventoPassado->name}): ".self::EQUIPES_EVENTO_PASSADO.' equipes, resultado publicado. '.
            "Evento atual ({$eventoAtual->name}): ".self::EQUIPES_EVENTO_ATUAL.' equipes inscritas, sem submissão ainda.'
        );
    }

    private function ensureEventoPassado(): Event
    {
        $event = Event::firstOrCreate(
            ['slug' => 'hackathon-ifpr-pinhais-2025'],
            [
                'name' => 'Edição piloto — Hackathon IFPR Pinhais',
                'edition' => 0,
                'status' => EventStatus::Finished,
                'description' => 'Primeira edição do hackathon, já encerrada. Dado de demonstração.',
                'registration_opens_at' => now()->subMonths(3),
                'registration_closes_at' => now()->subMonths(2),
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subMonths(2)->addDays(2),
                'submission_deadline' => now()->subMonths(2)->addDays(2),
                'voting_opens_at' => null,
                'voting_closes_at' => null,
                'results_published_at' => null,
                'min_team_size' => 2,
                'max_team_size' => 5,
                // Explícito mesmo tendo default(3) na migration: firstOrCreate
                // não recarrega o modelo depois do insert, e sem isto o
                // DistributeJudges lê null da instância em memória e não
                // atribui ninguém (null - 0 vira 0 em aritmética PHP, then
                // "$faltam <= 0" pula toda submissão).
                'judges_per_submission' => 3,
            ],
        );

        foreach (['Educação', 'Saúde', 'Cidade Inteligente'] as $name) {
            Track::firstOrCreate(
                ['event_id' => $event->id, 'name' => $name],
                ['description' => "Soluções para {$name}.", 'color' => '#2563eb'],
            );
        }

        return $event;
    }

    /**
     * @return Collection<int, Team>
     */
    private function popularEquipesEInscricoes(Event $event, int $total, bool $comSubmissao): Collection
    {
        $existentes = Team::forEvent($event)->count();
        $faltam = $total - $existentes;
        $trilhas = Track::where('event_id', $event->id)->get();

        for ($i = 0; $i < $faltam; $i++) {
            $lider = User::factory()->create(['email_verified_at' => now()]);
            $lider->assignRole(Role::Participante->value);
            EventRegistration::factory()->for($event)->for($lider)->create();

            $team = Team::factory()
                ->for($event)
                ->confirmada()
                ->create([
                    'leader_id' => $lider->id,
                    'track_id' => $trilhas->isNotEmpty() ? $trilhas->random()->id : null,
                ]);

            TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create([
                'status' => TeamMemberStatus::Active,
            ]);

            foreach (range(1, fake()->numberBetween(1, max(1, $event->max_team_size - 1))) as $ignored) {
                $membro = User::factory()->create(['email_verified_at' => now()]);
                $membro->assignRole(Role::Participante->value);
                EventRegistration::factory()->for($event)->for($membro)->create();

                TeamMember::factory()->for($event)->for($team)->for($membro)->create([
                    'status' => TeamMemberStatus::Active,
                ]);
            }

            if ($comSubmissao) {
                Submission::factory()->for($event)->for($team)->enviada()->create();
            }
        }

        return Team::forEvent($event)->get();
    }

    private function ensureRubricaAtiva(Event $event): Rubric
    {
        $rubrica = Rubric::forEvent($event)->where('is_active', true)->first();

        if ($rubrica) {
            return $rubrica->load('criteria');
        }

        $rubrica = Rubric::factory()->for($event)->ativa()->create(['name' => 'Rubrica oficial']);

        foreach ([
            ['name' => 'Inovação', 'weight' => '3.00', 'position' => 1],
            ['name' => 'Execução técnica', 'weight' => '3.00', 'position' => 2],
            ['name' => 'Impacto', 'weight' => '2.00', 'position' => 3],
            ['name' => 'Apresentação', 'weight' => '1.00', 'position' => 4],
        ] as $criterio) {
            Criterion::factory()->for($rubrica)->create($criterio);
        }

        return $rubrica->load('criteria');
    }

    /**
     * Evento encerrado: diferente do ensaio, aqui todo mundo já votou --
     * "já aconteceu" não teria avaliação pendente sobrando pra sempre.
     */
    private function ensureAvaliacoesCompletas(Event $event, Rubric $rubrica): void
    {
        app(DistributeJudges::class)->handle($event);

        $pendentes = JudgeAssignment::forEvent($event)
            ->whereDoesntHave('evaluation')
            ->get();

        foreach ($pendentes as $assignment) {
            $evaluation = new Evaluation;
            $evaluation->assignment_id = $assignment->id;
            $evaluation->status = EvaluationStatus::Draft;
            $evaluation->save();

            foreach ($rubrica->criteria as $criterio) {
                $score = new EvaluationScore;
                $score->evaluation_id = $evaluation->id;
                $score->criterion_id = $criterio->id;
                $score->score = fake()->randomFloat(2, 4, 10);
                $score->save();
            }

            $evaluation->status = EvaluationStatus::Submitted;
            $evaluation->submitted_at = now()->subMonths(2)->addDays(4);
            $evaluation->save();

            $assignment->status = JudgeAssignmentStatus::Done;
            $assignment->save();
        }
    }
}
