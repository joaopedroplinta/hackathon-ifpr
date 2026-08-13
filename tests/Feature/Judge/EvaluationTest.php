<?php

namespace Tests\Feature\Judge;

use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function jurado(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Jurado->value);

        return $user;
    }

    /**
     * @return array{0: Rubric, 1: Criterion, 2: Criterion}
     */
    private function rubricaAtiva(Event $event): array
    {
        $rubric = Rubric::factory()->for($event)->ativa()->create();
        $inovacao = Criterion::factory()->for($rubric)->create(['name' => 'Inovação', 'weight' => '2.00', 'max_score' => 10, 'position' => 0]);
        $execucao = Criterion::factory()->for($rubric)->create(['name' => 'Execução', 'weight' => '1.00', 'max_score' => 10, 'position' => 1]);

        return [$rubric, $inovacao, $execucao];
    }

    private function submissaoAtribuida(Event $event, User $jurado): array
    {
        $team = Team::factory()->for($event)->create();
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submission->id;
        $assignment->assigned_at = now();
        $assignment->save();

        return [$submission, $assignment];
    }

    public function test_autosave_persists_a_draft_without_submitting_it(): void
    {
        $event = Event::factory()->create();
        [, $inovacao, $execucao] = $this->rubricaAtiva($event);
        $jurado = $this->jurado();
        [$submission, $assignment] = $this->submissaoAtribuida($event, $jurado);

        $this->actingAs($jurado)
            ->post(route('jurado.avaliar.autosave', $submission), [
                'scores' => [
                    ['criterion_id' => $inovacao->id, 'score' => 8, 'comment' => null],
                    ['criterion_id' => $execucao->id, 'score' => null, 'comment' => null],
                ],
                'overall_comment' => null,
            ])
            ->assertRedirect();

        $evaluation = Evaluation::firstOrFail();
        $this->assertSame(EvaluationStatus::Draft, $evaluation->status);
        $this->assertNull($evaluation->submitted_at);
        $this->assertSame(1, Evaluation::count());
        $this->assertSame(JudgeAssignmentStatus::InProgress, $assignment->fresh()->status);
    }

    public function test_submitting_requires_a_score_for_every_criterion_of_the_active_rubric(): void
    {
        $event = Event::factory()->create();
        [, $inovacao, $execucao] = $this->rubricaAtiva($event);
        $jurado = $this->jurado();
        [$submission] = $this->submissaoAtribuida($event, $jurado);

        $this->actingAs($jurado)
            ->post(route('jurado.avaliar.enviar', $submission), [
                'scores' => [
                    ['criterion_id' => $inovacao->id, 'score' => 8, 'comment' => null],
                    ['criterion_id' => $execucao->id, 'score' => null, 'comment' => null],
                ],
                'overall_comment' => null,
            ])
            ->assertSessionHasErrors('scores');

        $this->assertSame(0, Evaluation::where('status', EvaluationStatus::Submitted)->count());
    }

    /**
     * A nota acima do máximo cai no índice do item (ex.: "scores.0.score"),
     * não na chave "scores" -- a tela em avaliar.tsx lê exatamente essa
     * chave indexada pra mostrar o erro embaixo do campo certo.
     */
    public function test_a_score_above_the_criterion_maximum_is_refused_with_an_indexed_error(): void
    {
        $event = Event::factory()->create();
        [, $inovacao, $execucao] = $this->rubricaAtiva($event);
        $jurado = $this->jurado();
        [$submission] = $this->submissaoAtribuida($event, $jurado);

        $this->actingAs($jurado)
            ->post(route('jurado.avaliar.enviar', $submission), [
                'scores' => [
                    ['criterion_id' => $inovacao->id, 'score' => 20, 'comment' => null],
                    ['criterion_id' => $execucao->id, 'score' => 8, 'comment' => null],
                ],
                'overall_comment' => null,
            ])
            ->assertSessionHasErrors('scores.0.score');

        $this->assertSame(0, Evaluation::where('status', EvaluationStatus::Submitted)->count());
    }

    public function test_submitting_with_every_criterion_scored_marks_the_evaluation_as_submitted(): void
    {
        $event = Event::factory()->create();
        [, $inovacao, $execucao] = $this->rubricaAtiva($event);
        $jurado = $this->jurado();
        [$submission, $assignment] = $this->submissaoAtribuida($event, $jurado);

        $this->actingAs($jurado)
            ->post(route('jurado.avaliar.enviar', $submission), [
                'scores' => [
                    ['criterion_id' => $inovacao->id, 'score' => 8, 'comment' => null],
                    ['criterion_id' => $execucao->id, 'score' => 7, 'comment' => 'Boa execução'],
                ],
                'overall_comment' => 'Projeto sólido.',
            ])
            ->assertRedirect(route('jurado.index'));

        $evaluation = Evaluation::firstOrFail();
        $this->assertSame(EvaluationStatus::Submitted, $evaluation->status);
        $this->assertNotNull($evaluation->submitted_at);
        $this->assertSame(JudgeAssignmentStatus::Done, $assignment->fresh()->status);
        $this->assertSame(2, $evaluation->scores()->count());
    }

    public function test_a_judge_cannot_edit_an_evaluation_already_submitted(): void
    {
        $event = Event::factory()->create();
        [, $inovacao, $execucao] = $this->rubricaAtiva($event);
        $jurado = $this->jurado();
        [$submission] = $this->submissaoAtribuida($event, $jurado);

        $payload = [
            'scores' => [
                ['criterion_id' => $inovacao->id, 'score' => 8, 'comment' => null],
                ['criterion_id' => $execucao->id, 'score' => 7, 'comment' => null],
            ],
            'overall_comment' => null,
        ];

        $this->actingAs($jurado)->post(route('jurado.avaliar.enviar', $submission), $payload);

        $this->actingAs($jurado)
            ->post(route('jurado.avaliar.autosave', $submission), $payload)
            ->assertForbidden();
    }

    public function test_a_judge_gets_a_403_on_a_submission_not_assigned_to_them(): void
    {
        $event = Event::factory()->create();
        $this->rubricaAtiva($event);
        $jurado = $this->jurado();
        $team = Team::factory()->for($event)->create();
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $this->actingAs($jurado)
            ->get(route('jurado.avaliar.show', $submission))
            ->assertForbidden();
    }

    public function test_the_queue_shows_how_many_assignments_have_been_evaluated(): void
    {
        $event = Event::factory()->create();
        [, $inovacao, $execucao] = $this->rubricaAtiva($event);
        $jurado = $this->jurado();

        [$submissaoUm] = $this->submissaoAtribuida($event, $jurado);
        [$submissaoDois] = $this->submissaoAtribuida($event, $jurado);
        $this->submissaoAtribuida($event, $jurado);

        $this->actingAs($jurado)->post(route('jurado.avaliar.enviar', $submissaoUm), [
            'scores' => [
                ['criterion_id' => $inovacao->id, 'score' => 8, 'comment' => null],
                ['criterion_id' => $execucao->id, 'score' => 7, 'comment' => null],
            ],
            'overall_comment' => null,
        ]);

        $this->actingAs($jurado)->post(route('jurado.avaliar.autosave', $submissaoDois), [
            'scores' => [
                ['criterion_id' => $inovacao->id, 'score' => 5, 'comment' => null],
                ['criterion_id' => $execucao->id, 'score' => null, 'comment' => null],
            ],
            'overall_comment' => null,
        ]);

        $response = $this->actingAs($jurado)->get(route('jurado.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('progresso.avaliadas', 1)
            ->where('progresso.total', 3)
        );
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $event = Event::factory()->create();
        $this->rubricaAtiva($event);
        $jurado = $this->jurado();
        [$submission] = $this->submissaoAtribuida($event, $jurado);

        $this->get(route('jurado.avaliar.show', $submission))->assertRedirect(route('login'));
    }
}
