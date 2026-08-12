<?php

namespace Database\Seeders;

use App\Actions\Judging\DistributeJudges;
use App\Enums\EvaluationStatus;
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
 * Popula o evento atual pra semelhar o dia cheio: 30 equipes, submissões em
 * estados variados, os 5 jurados avaliando de verdade. PLANO.md, semana 8
 * ("seed com 30 equipes e 5 jurados"). Trabalhar com banco vazio esconde
 * problema que só aparece sob carga -- é exatamente o que o ensaio existe
 * pra evitar.
 *
 * Idempotente como o DatabaseSeeder: completa até o total, não duplica se
 * rodar de novo (migrate:fresh está negado).
 */
class EnsaioSeeder extends Seeder
{
    private const TOTAL_EQUIPES = 30;

    public function run(): void
    {
        $event = Event::current();

        if (! $event) {
            $this->command?->error('Nenhum evento publicado. Rode o DatabaseSeeder primeiro.');

            return;
        }

        $rubrica = $this->ensureRubricaAtiva($event);
        $equipes = $this->ensureEquipes($event);
        $this->ensureSubmissoes($event, $equipes);

        app(DistributeJudges::class)->handle($event);

        $this->ensureAvaliacoes($event, $rubrica);

        $this->command?->info(
            self::TOTAL_EQUIPES.' equipes, '.Submission::forEvent($event)->count().' submissões, '.
            JudgeAssignment::forEvent($event)->count().' atribuições de jurado.'
        );
    }

    private function ensureRubricaAtiva(Event $event): Rubric
    {
        $rubrica = Rubric::forEvent($event)->where('is_active', true)->first();

        if ($rubrica) {
            return $rubrica;
        }

        $rubrica = Rubric::factory()->for($event)->ativa()->create(['name' => 'Rubrica do ensaio']);

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
     * @return Collection<int, Team>
     */
    private function ensureEquipes(Event $event): Collection
    {
        $existentes = Team::forEvent($event)->count();
        $faltam = self::TOTAL_EQUIPES - $existentes;
        $trilhas = Track::where('event_id', $event->id)->get();

        for ($i = 0; $i < $faltam; $i++) {
            $lider = User::factory()->create(['email_verified_at' => now()]);
            $lider->assignRole(Role::Participante->value);
            $this->registrarNoEvento($event, $lider);

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
                $this->registrarNoEvento($event, $membro);

                TeamMember::factory()->for($event)->for($team)->for($membro)->create([
                    'status' => TeamMemberStatus::Active,
                ]);
            }
        }

        return Team::forEvent($event)->get();
    }

    private function registrarNoEvento(Event $event, User $user): void
    {
        EventRegistration::factory()->for($event)->for($user)->create();
    }

    /**
     * @param  Collection<int, Team>  $equipes
     */
    private function ensureSubmissoes(Event $event, Collection $equipes): void
    {
        $semSubmissao = $equipes->filter(fn (Team $t) => ! $t->submission)->values();

        // Mantém uma fração sem envio de propósito -- painel de pendências
        // não pode ficar zerado no ensaio, senão ninguém percebe que o
        // aviso funciona.
        $quantidadeSemEnvio = (int) round($semSubmissao->count() * 0.1);

        foreach ($semSubmissao as $index => $team) {
            if ($index < $quantidadeSemEnvio) {
                continue;
            }

            $atrasada = fake()->boolean(10);

            Submission::factory()
                ->for($event)
                ->for($team)
                ->{$atrasada ? 'atrasada' : 'enviada'}()
                ->create();
        }
    }

    private function ensureAvaliacoes(Event $event, Rubric $rubrica): void
    {
        $pendentes = JudgeAssignment::forEvent($event)
            ->whereDoesntHave('evaluation')
            ->get();

        foreach ($pendentes as $assignment) {
            $evaluation = new Evaluation;
            $evaluation->assignment_id = $assignment->id;
            $evaluation->status = EvaluationStatus::Draft;
            $evaluation->save();

            // 70% das avaliações vão até o fim -- o resto fica em aberto de
            // propósito, pro painel "avaliações em aberto" ter o que mostrar.
            if (! fake()->boolean(70)) {
                continue;
            }

            foreach ($rubrica->criteria as $criterio) {
                $score = new EvaluationScore;
                $score->evaluation_id = $evaluation->id;
                $score->criterion_id = $criterio->id;
                $score->score = fake()->randomFloat(2, 4, 10);
                $score->save();
            }

            $evaluation->status = EvaluationStatus::Submitted;
            $evaluation->submitted_at = now();
            $evaluation->save();

            $assignment->status = JudgeAssignmentStatus::Done;
            $assignment->save();
        }
    }
}
