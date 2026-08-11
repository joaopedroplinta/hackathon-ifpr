<?php

namespace Tests\Feature\Organizer;

use App\Actions\Judging\DistributeJudges;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Models\ConflictOfInterest;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class JudgeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function organizador(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Organizador->value);

        return $user;
    }

    private function jurado(string $nome = 'Jurado'): User
    {
        $user = User::factory()->create(['name' => $nome, 'email_verified_at' => now()]);
        $user->assignRole(Role::Jurado->value);

        return $user;
    }

    private function participante(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);

        return $user;
    }

    private function submissaoEnviada(Event $event, string $nomeEquipe = 'Equipe'): Submission
    {
        $team = Team::factory()->for($event)->create(['name' => $nomeEquipe]);

        return Submission::factory()->for($event)->for($team)->enviada()->create();
    }

    public function test_the_action_distributes_the_configured_number_of_judges(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 2]);
        $submissao = $this->submissaoEnviada($event);
        $this->jurado('Ana');
        $this->jurado('Bruno');
        $this->jurado('Carla');

        $resultado = app(DistributeJudges::class)->handle($event);

        $this->assertSame(2, $resultado['criadas']);
        $this->assertSame(2, JudgeAssignment::where('submission_id', $submissao->id)->count());
    }

    /** Conflito de interesse bloqueia, nunca só avisa -- regras-avaliacao. */
    public function test_the_action_never_assigns_a_judge_with_a_conflict(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        $team = Team::factory()->for($event)->create();
        $submissao = Submission::factory()->for($event)->for($team)->enviada()->create();

        $comConflito = $this->jurado('Com conflito');
        $semConflito = $this->jurado('Sem conflito');
        ConflictOfInterest::factory()->create(['judge_id' => $comConflito->id, 'team_id' => $team->id]);

        app(DistributeJudges::class)->handle($event);

        $atribuido = JudgeAssignment::where('submission_id', $submissao->id)->first();
        $this->assertSame($semConflito->id, $atribuido->judge_id);
    }

    public function test_the_action_reports_a_submission_with_no_eligible_judge(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        $team = Team::factory()->for($event)->create();
        Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'Único projeto']);

        $unico = $this->jurado('Único jurado');
        ConflictOfInterest::factory()->create(['judge_id' => $unico->id, 'team_id' => $team->id]);

        $resultado = app(DistributeJudges::class)->handle($event);

        $this->assertSame(0, $resultado['criadas']);
        $this->assertSame(['Único projeto'], $resultado['sem_jurado_elegivel']);
    }

    public function test_load_is_balanced_across_judges(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        for ($i = 0; $i < 4; $i++) {
            $this->submissaoEnviada($event, "Equipe {$i}");
        }
        $this->jurado('Ana');
        $this->jurado('Bruno');

        app(DistributeJudges::class)->handle($event);

        $cargas = JudgeAssignment::forEvent($event)
            ->selectRaw('judge_id, count(*) as total')
            ->groupBy('judge_id')
            ->pluck('total');

        $this->assertSame(2, $cargas->count());
        $this->assertSame(2, (int) $cargas->max());
        $this->assertSame(2, (int) $cargas->min());
    }

    /** Rodar de novo não sobrescreve nem duplica o que já existe. */
    public function test_redistributing_never_touches_an_existing_assignment_manual_or_automatic(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        $submissao = $this->submissaoEnviada($event);
        $manual = $this->jurado('Atribuído na mão');
        $this->jurado('Outro jurado disponível');

        $atribuicao = new JudgeAssignment;
        $atribuicao->event_id = $event->id;
        $atribuicao->judge_id = $manual->id;
        $atribuicao->submission_id = $submissao->id;
        $atribuicao->assigned_at = now();
        $atribuicao->save();

        $resultado = app(DistributeJudges::class)->handle($event);

        $this->assertSame(0, $resultado['criadas']);
        $this->assertSame(1, JudgeAssignment::where('submission_id', $submissao->id)->count());
        $this->assertSame($manual->id, JudgeAssignment::where('submission_id', $submissao->id)->firstOrFail()->judge_id);
    }

    public function test_staff_runs_the_automatic_distribution(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        $this->submissaoEnviada($event);
        $this->jurado();

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.distribute'))
            ->assertRedirect(route('admin.jurados.index'))
            ->assertSessionHas('sucesso');

        $this->assertSame(1, JudgeAssignment::count());
    }

    public function test_a_participant_cannot_run_the_distribution(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->post(route('admin.jurados.distribute'))
            ->assertForbidden();
    }

    public function test_staff_manually_assigns_a_judge(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->submissaoEnviada($event);
        $jurado = $this->jurado();

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.store'), ['judge_id' => $jurado->id, 'submission_id' => $submissao->id])
            ->assertRedirect();

        $this->assertSame(1, JudgeAssignment::count());
    }

    /** O bloqueio vale pro ajuste manual também, não só pra distribuição automática. */
    public function test_manual_assignment_is_blocked_by_a_conflict_of_interest(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $submissao = Submission::factory()->for($event)->for($team)->enviada()->create();
        $jurado = $this->jurado();
        ConflictOfInterest::factory()->create(['judge_id' => $jurado->id, 'team_id' => $team->id]);

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.store'), ['judge_id' => $jurado->id, 'submission_id' => $submissao->id])
            ->assertSessionHasErrors('judge_id');

        $this->assertSame(0, JudgeAssignment::count());
    }

    public function test_manual_assignment_is_blocked_when_the_judge_is_already_assigned(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->submissaoEnviada($event);
        $jurado = $this->jurado();

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->assigned_at = now();
        $assignment->save();

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.store'), ['judge_id' => $jurado->id, 'submission_id' => $submissao->id])
            ->assertSessionHasErrors('judge_id');

        $this->assertSame(1, JudgeAssignment::count());
    }

    public function test_staff_removes_an_assignment(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->submissaoEnviada($event);
        $jurado = $this->jurado();

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->assigned_at = now();
        $assignment->save();

        $this->actingAs($this->organizador())
            ->delete(route('admin.jurados.destroy', $assignment))
            ->assertRedirect();

        $this->assertSame(0, JudgeAssignment::count());
    }

    /** Jurado ausente, 1 clique: remove e já preenche a vaga com outro elegível. */
    public function test_reassigning_removes_the_absent_judge_and_fills_the_slot(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        $submissao = $this->submissaoEnviada($event);
        $ausente = $this->jurado('Ausente');
        $disponivel = $this->jurado('Disponível');

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $ausente->id;
        $assignment->submission_id = $submissao->id;
        $assignment->assigned_at = now();
        $assignment->save();

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.reassign', $assignment))
            ->assertRedirect();

        $this->assertSame(0, JudgeAssignment::where('judge_id', $ausente->id)->count());
        $this->assertSame(1, JudgeAssignment::where('judge_id', $disponivel->id)->where('submission_id', $submissao->id)->count());
    }

    public function test_staff_registers_a_conflict_of_interest(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $jurado = $this->jurado();

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.conflicts.store'), ['judge_id' => $jurado->id, 'team_id' => $team->id, 'reason' => 'Parente'])
            ->assertRedirect();

        $this->assertSame(1, ConflictOfInterest::count());
    }

    public function test_staff_updates_how_many_judges_per_submission(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 3]);

        $this->actingAs($this->organizador())
            ->patch(route('admin.jurados.config'), ['judges_per_submission' => 5])
            ->assertRedirect();

        $this->assertSame(5, $event->fresh()->judges_per_submission);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.jurados.index'))->assertRedirect(route('login'));
    }

    public function test_a_judge_cannot_manage_assignments(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->submissaoEnviada($event);
        $jurado = $this->jurado();

        $this->actingAs($jurado)
            ->get(route('admin.jurados.index'))
            ->assertForbidden();

        $this->actingAs($jurado)
            ->post(route('admin.jurados.store'), ['judge_id' => $jurado->id, 'submission_id' => $submissao->id])
            ->assertForbidden();
    }

    /**
     * @return array{0: Submission, 1: JudgeAssignment, 2: Evaluation}
     */
    private function avaliacaoEnviada(Event $event, User $jurado): array
    {
        $submissao = $this->submissaoEnviada($event);
        $team = $submissao->team;

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->status = JudgeAssignmentStatus::Done;
        $assignment->assigned_at = now();
        $assignment->save();

        $evaluation = new Evaluation;
        $evaluation->assignment_id = $assignment->id;
        $evaluation->status = EvaluationStatus::Submitted;
        $evaluation->submitted_at = now();
        $evaluation->save();

        return [$submissao, $assignment->fresh(), $evaluation];
    }

    public function test_staff_reopens_a_submitted_evaluation_with_a_reason(): void
    {
        $event = Event::factory()->create();
        $jurado = $this->jurado();
        [, $assignment, $evaluation] = $this->avaliacaoEnviada($event, $jurado);
        $organizador = $this->organizador();

        $this->actingAs($organizador)
            ->post(route('admin.jurados.reopen-evaluation', $assignment), [
                'reason' => 'Jurado percebeu que trocou a nota de dois critérios sem querer.',
            ])
            ->assertRedirect(route('admin.jurados.index'));

        $evaluation->refresh();
        $this->assertSame(EvaluationStatus::Draft, $evaluation->status);
        $this->assertNull($evaluation->submitted_at);
        $this->assertSame(JudgeAssignmentStatus::InProgress, $assignment->fresh()->status);

        $log = Activity::latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($organizador->id, $log->causer_id);
        $this->assertSame($evaluation->id, $log->subject_id);
        $this->assertSame('Jurado percebeu que trocou a nota de dois critérios sem querer.', $log->properties['motivo']);
    }

    public function test_reopening_requires_a_reason_with_enough_detail(): void
    {
        $event = Event::factory()->create();
        $jurado = $this->jurado();
        [, $assignment] = $this->avaliacaoEnviada($event, $jurado);

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.reopen-evaluation', $assignment), ['reason' => 'curto'])
            ->assertSessionHasErrors('reason');
    }

    public function test_a_judge_cannot_reopen_an_evaluation(): void
    {
        $event = Event::factory()->create();
        $jurado = $this->jurado();
        [, $assignment] = $this->avaliacaoEnviada($event, $jurado);

        $this->actingAs($jurado)
            ->post(route('admin.jurados.reopen-evaluation', $assignment), [
                'reason' => 'Motivo qualquer com mais de dez caracteres.',
            ])
            ->assertForbidden();
    }

    public function test_a_draft_evaluation_cannot_be_reopened(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->submissaoEnviada($event);
        $jurado = $this->jurado();

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->assigned_at = now();
        $assignment->save();

        $evaluation = new Evaluation;
        $evaluation->assignment_id = $assignment->id;
        $evaluation->status = EvaluationStatus::Draft;
        $evaluation->save();

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.reopen-evaluation', $assignment), [
                'reason' => 'Motivo qualquer com mais de dez caracteres.',
            ])
            ->assertForbidden();
    }

    public function test_an_assignment_without_any_evaluation_cannot_be_reopened(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->submissaoEnviada($event);
        $jurado = $this->jurado();

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->assigned_at = now();
        $assignment->save();

        $this->actingAs($this->organizador())
            ->post(route('admin.jurados.reopen-evaluation', $assignment), [
                'reason' => 'Motivo qualquer com mais de dez caracteres.',
            ])
            ->assertForbidden();
    }
}
