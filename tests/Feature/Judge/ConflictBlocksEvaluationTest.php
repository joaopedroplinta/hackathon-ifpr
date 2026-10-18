<?php

namespace Tests\Feature\Judge;

use App\Actions\Judging\DistributeJudges;
use App\Enums\EvaluationStatus;
use App\Enums\Role;
use App\Models\ConflictOfInterest;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O conflito de interesse bloqueia, não só avisa -- e vale também quando o
 * jurado é membro ativo da equipe (jurado promovido que não saiu dela), sem
 * depender de alguém registrar o conflito.
 */
class ConflictBlocksEvaluationTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private Criterion $criterion;

    private Team $team;

    private Submission $submission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->event = Event::factory()->create();
        $rubric = Rubric::factory()->for($this->event)->ativa()->create();
        $this->criterion = Criterion::factory()->for($rubric)->create(['weight' => '1.00', 'max_score' => 10, 'position' => 0]);
        $this->team = Team::factory()->for($this->event)->create();
        $this->submission = Submission::factory()->for($this->event)->for($this->team)->enviada()->create();
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role->value);

        return $user;
    }

    private function assign(User $judge): JudgeAssignment
    {
        $assignment = new JudgeAssignment;
        $assignment->event_id = $this->event->id;
        $assignment->judge_id = $judge->id;
        $assignment->submission_id = $this->submission->id;
        $assignment->assigned_at = now();
        $assignment->save();

        return $assignment;
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return ['scores' => [['criterion_id' => $this->criterion->id, 'score' => 9, 'comment' => null]], 'overall_comment' => 'x'];
    }

    public function test_a_conflict_registered_after_the_assignment_removes_it_and_closes_the_evaluation(): void
    {
        $judge = $this->user(Role::Jurado);
        $this->assign($judge);

        $this->actingAs($this->user(Role::Organizador))
            ->post(route('painel.jurados.conflicts.store'), ['judge_id' => $judge->id, 'team_id' => $this->team->id, 'reason' => 'Parente próximo'])
            ->assertRedirect();

        $this->assertSame(0, JudgeAssignment::count(), 'A atribuição pendente deveria sair junto com o conflito.');

        $this->actingAs($judge)->get(route('jurado.avaliar.show', $this->submission))->assertForbidden();
        $this->actingAs($judge)->post(route('jurado.avaliar.enviar', $this->submission), $this->payload())->assertForbidden();
        $this->assertSame(0, Evaluation::count());
    }

    public function test_an_already_submitted_evaluation_is_kept_when_a_conflict_is_registered_later(): void
    {
        $judge = $this->user(Role::Jurado);
        $assignment = $this->assign($judge);
        $evaluation = new Evaluation;
        $evaluation->assignment_id = $assignment->id;
        $evaluation->status = EvaluationStatus::Submitted;
        $evaluation->submitted_at = now();
        $evaluation->save();

        $this->actingAs($this->user(Role::Organizador))
            ->post(route('painel.jurados.conflicts.store'), ['judge_id' => $judge->id, 'team_id' => $this->team->id, 'reason' => 'Descoberto depois'])
            ->assertRedirect();

        $this->assertSame(1, JudgeAssignment::count(), 'Nota enviada é registro histórico.');
        $this->assertSame(1, ConflictOfInterest::count());
    }

    public function test_a_judge_who_is_an_active_member_of_the_team_is_never_distributed_to_it(): void
    {
        $judge = $this->user(Role::Jurado);
        TeamMember::factory()->for($this->event)->for($this->team)->for($judge)->create();
        // Um jurado só: se não fosse a regra, ele seria o escolhido.
        $this->event->forceFill(['judges_per_submission' => 1])->save();

        $result = app(DistributeJudges::class)->handle($this->event->fresh());

        $this->assertSame(0, $result['criadas']);
        $this->assertSame(0, JudgeAssignment::count());
    }

    public function test_a_judge_who_is_a_member_of_the_team_cannot_be_assigned_manually(): void
    {
        $judge = $this->user(Role::Jurado);
        TeamMember::factory()->for($this->event)->for($this->team)->for($judge)->create();

        $this->actingAs($this->user(Role::Organizador))
            ->post(route('painel.jurados.store'), ['judge_id' => $judge->id, 'submission_id' => $this->submission->id])
            ->assertSessionHasErrors('judge_id');

        $this->assertSame(0, JudgeAssignment::count());
    }

    public function test_a_member_judge_with_a_pre_existing_assignment_cannot_open_or_submit_the_evaluation(): void
    {
        $judge = $this->user(Role::Jurado);
        TeamMember::factory()->for($this->event)->for($this->team)->for($judge)->create();
        $this->assign($judge);

        $this->actingAs($judge)->get(route('jurado.avaliar.show', $this->submission))->assertForbidden();
        $this->actingAs($judge)->post(route('jurado.avaliar.enviar', $this->submission), $this->payload())->assertForbidden();
        $this->assertSame(0, Evaluation::count());
    }

    public function test_a_judge_without_conflict_still_evaluates_normally(): void
    {
        $judge = $this->user(Role::Jurado);
        $this->assign($judge);

        $this->actingAs($judge)->get(route('jurado.avaliar.show', $this->submission))->assertOk();
        $this->actingAs($judge)->post(route('jurado.avaliar.enviar', $this->submission), $this->payload())
            ->assertRedirect(route('jurado.index'));

        $this->assertSame(EvaluationStatus::Submitted, Evaluation::firstOrFail()->status);
    }
}
