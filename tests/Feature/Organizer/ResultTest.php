<?php

namespace Tests\Feature\Organizer;

use App\Actions\Results\ComputeResults;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Result;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultTest extends TestCase
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

    private function participante(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);

        return $user;
    }

    private function jurado(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Jurado->value);

        return $user;
    }

    private function submissaoEnviada(Event $event): Submission
    {
        $team = Team::factory()->for($event)->create();

        return Submission::factory()->for($event)->for($team)->enviada()->create();
    }

    private function avaliacaoSubmetida(Event $event, Submission $submissao, User $jurado, Criterion $criterio, float $score): void
    {
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

        $evaluationScore = new EvaluationScore;
        $evaluationScore->evaluation_id = $evaluation->id;
        $evaluationScore->criterion_id = $criterio->id;
        $evaluationScore->score = $score;
        $evaluationScore->save();
    }

    /** Atribuição sem avaliação enviada -- jurado incompleto. */
    private function assignmentPendente(Event $event, Submission $submissao, User $jurado): void
    {
        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->assigned_at = now();
        $assignment->save();
    }

    public function test_staff_sees_pendencies_when_no_evaluation_was_submitted(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->ativa()->create();
        Criterion::factory()->for($rubric)->create(['weight' => '1.00']);
        $submissao = $this->submissaoEnviada($event);
        $this->assignmentPendente($event, $submissao, $this->jurado());

        $response = $this->actingAs($this->organizador())->get(route('admin.resultados.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('pendencias.submissoes_sem_nota', [$submissao->title])
            ->where('pendencias.jurados_incompletos.0.enviadas', 0)
            ->where('pendencias.jurados_incompletos.0.total', 1)
        );
    }

    public function test_staff_recomputes_results(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->ativa()->create();
        $criterio = Criterion::factory()->for($rubric)->create(['weight' => '1.00']);
        $submissao = $this->submissaoEnviada($event);
        $this->avaliacaoSubmetida($event, $submissao, $this->jurado(), $criterio, 8);

        $this->actingAs($this->organizador())
            ->post(route('admin.resultados.recompute'))
            ->assertRedirect(route('admin.resultados.index'));

        $result = Result::forEvent($event)->where('submission_id', $submissao->id)->firstOrFail();
        $this->assertSame('8.00', (string) $result->final_score);
    }

    public function test_publishing_without_pendencies_works_directly(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->ativa()->create();
        $criterio = Criterion::factory()->for($rubric)->create(['weight' => '1.00']);
        $submissao = $this->submissaoEnviada($event);
        $this->avaliacaoSubmetida($event, $submissao, $this->jurado(), $criterio, 8);

        app(ComputeResults::class)->handle($event);

        $this->actingAs($this->organizador())
            ->post(route('admin.resultados.publish'))
            ->assertRedirect(route('admin.resultados.index'))
            ->assertSessionHas('sucesso');

        $this->assertNotNull($event->fresh()->results_published_at);
    }

    public function test_publishing_with_pendencies_requires_confirmation(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->ativa()->create();
        Criterion::factory()->for($rubric)->create(['weight' => '1.00']);
        $submissao = $this->submissaoEnviada($event);
        $this->assignmentPendente($event, $submissao, $this->jurado());

        $this->actingAs($this->organizador())
            ->post(route('admin.resultados.publish'))
            ->assertSessionHas('erro');

        $this->assertNull($event->fresh()->results_published_at);

        $this->actingAs($this->organizador())
            ->post(route('admin.resultados.publish'), ['confirmar_pendencias' => true])
            ->assertRedirect(route('admin.resultados.index'))
            ->assertSessionHas('sucesso');

        $this->assertNotNull($event->fresh()->results_published_at);
    }

    public function test_a_participant_cannot_access_results_management(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('admin.resultados.index'))
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->post(route('admin.resultados.recompute'))
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->post(route('admin.resultados.publish'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.resultados.index'))->assertRedirect(route('login'));
    }
}
