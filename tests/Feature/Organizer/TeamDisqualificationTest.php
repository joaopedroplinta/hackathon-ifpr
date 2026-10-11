<?php

namespace Tests\Feature\Organizer;

use App\Actions\Results\ComputeResults;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Result;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class TeamDisqualificationTest extends TestCase
{
    use RefreshDatabase;

    private const MOTIVO = 'Projeto copiado de repositório público sem declarar a origem.';

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

    /**
     * @return array{0: Team, 1: Submission}
     */
    private function equipeComEnvio(Event $event): array
    {
        $team = Team::factory()->for($event)->create(['status' => TeamStatus::Confirmed]);
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        return [$team, $submission];
    }

    public function test_staff_disqualifies_the_team_and_its_submission_with_a_reason(): void
    {
        $event = Event::factory()->create();
        [$team, $submission] = $this->equipeComEnvio($event);
        $organizador = $this->organizador();

        $this->actingAs($organizador)
            ->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO])
            ->assertSessionHas('sucesso');

        $this->assertSame(TeamStatus::Disqualified, $team->fresh()->status);
        $this->assertSame(SubmissionStatus::Disqualified, $submission->fresh()->status);

        $log = Activity::where('description', 'Equipe desclassificada')->first();
        $this->assertNotNull($log);
        $this->assertSame($organizador->id, $log->causer_id);
        $this->assertSame($team->id, $log->subject_id);
        $this->assertSame(self::MOTIVO, $log->properties['motivo']);
        $this->assertSame('confirmed', $log->properties['equipe_de']);
        $this->assertSame('submitted', $log->properties['submissao_de']);
    }

    public function test_a_team_without_a_submission_can_be_disqualified(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create(['status' => TeamStatus::Confirmed]);

        $this->actingAs($this->organizador())
            ->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO])
            ->assertSessionHas('sucesso');

        $this->assertSame(TeamStatus::Disqualified, $team->fresh()->status);
    }

    public function test_a_reason_with_enough_detail_is_required(): void
    {
        $event = Event::factory()->create();
        [$team] = $this->equipeComEnvio($event);

        $this->actingAs($this->organizador())
            ->post(route('painel.teams.disqualify', $team), ['motivo' => 'curto'])
            ->assertSessionHasErrors('motivo');

        $this->assertSame(TeamStatus::Confirmed, $team->fresh()->status);
    }

    public function test_a_participant_cannot_disqualify(): void
    {
        $event = Event::factory()->create();
        [$team] = $this->equipeComEnvio($event);
        $participante = User::factory()->create(['email_verified_at' => now()]);
        $participante->assignRole(Role::Participante->value);

        $this->actingAs($participante)
            ->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO])
            ->assertForbidden();

        $this->assertSame(TeamStatus::Confirmed, $team->fresh()->status);
    }

    public function test_the_two_actions_only_apply_to_the_matching_status(): void
    {
        $event = Event::factory()->create();
        [$ativa] = $this->equipeComEnvio($event);
        $desclassificada = Team::factory()->for($event)->create(['status' => TeamStatus::Disqualified]);
        $organizador = $this->organizador();

        $this->actingAs($organizador)->post(route('painel.teams.reinstate', $ativa), ['motivo' => self::MOTIVO])->assertForbidden();
        $this->actingAs($organizador)->post(route('painel.teams.disqualify', $desclassificada), ['motivo' => self::MOTIVO])->assertForbidden();
    }

    public function test_a_team_from_another_edition_cannot_be_disqualified(): void
    {
        Event::factory()->create(['edition' => 2]);
        $antigo = Event::factory()->create(['edition' => 1]);
        [$team] = $this->equipeComEnvio($antigo);

        $this->actingAs($this->organizador())
            ->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO])
            ->assertNotFound();

        $this->assertSame(TeamStatus::Confirmed, $team->fresh()->status);
    }

    public function test_reinstating_restores_the_previous_statuses_and_is_logged(): void
    {
        $event = Event::factory()->create();
        [$team, $submission] = $this->equipeComEnvio($event);
        $organizador = $this->organizador();

        $this->actingAs($organizador)->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO]);
        $this->actingAs($organizador)
            ->post(route('painel.teams.reinstate', $team), ['motivo' => 'Origem comprovada pela equipe, caso encerrado.'])
            ->assertSessionHas('sucesso');

        $this->assertSame(TeamStatus::Confirmed, $team->fresh()->status);
        $this->assertSame(SubmissionStatus::Submitted, $submission->fresh()->status);
        $this->assertSame(1, Activity::where('description', 'Equipe reabilitada')->count());
    }

    public function test_reinstating_a_late_accepted_team_restores_the_late_accepted_status(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create(['status' => TeamStatus::Confirmed]);
        $submission = Submission::factory()->for($event)->for($team)->atrasada()->create(['status' => SubmissionStatus::LateAccepted]);
        $organizador = $this->organizador();

        $this->actingAs($organizador)->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO]);
        $this->actingAs($organizador)->post(route('painel.teams.reinstate', $team), ['motivo' => 'Decisão revista pela comissão.']);

        $this->assertSame(SubmissionStatus::LateAccepted, $submission->fresh()->status);
    }

    public function test_disqualifying_removes_the_team_from_the_computed_results_and_reinstating_brings_it_back(): void
    {
        $event = Event::factory()->create();
        [$teamA, $submissaoA] = $this->equipeComEnvio($event);
        [, $submissaoB] = $this->equipeComEnvio($event);
        app(ComputeResults::class)->handle($event);
        $this->assertSame(2, Result::forEvent($event)->count());

        $organizador = $this->organizador();
        $this->actingAs($organizador)->post(route('painel.teams.disqualify', $teamA), ['motivo' => self::MOTIVO]);

        $this->assertNull(Result::forEvent($event)->where('submission_id', $submissaoA->id)->first());
        $this->assertNotNull(Result::forEvent($event)->where('submission_id', $submissaoB->id)->first());

        $this->actingAs($organizador)->post(route('painel.teams.reinstate', $teamA), ['motivo' => 'Decisão revista pela comissão.']);

        $this->assertSame(2, Result::forEvent($event)->count());
    }

    public function test_disqualifying_after_publication_logs_the_ranking_change(): void
    {
        $event = Event::factory()->create(['results_published_at' => now()->subDay()]);
        [$team] = $this->equipeComEnvio($event);
        app(ComputeResults::class)->handle($event);

        $this->actingAs($this->organizador())->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO]);

        $this->assertSame(1, Activity::where('description', 'Resultado recalculado após a publicação')->count());
    }

    public function test_recomputing_drops_the_row_of_a_submission_that_no_longer_competes(): void
    {
        $event = Event::factory()->create();
        [, $submissaoA] = $this->equipeComEnvio($event);
        $this->equipeComEnvio($event);
        app(ComputeResults::class)->handle($event);

        $submissaoA->status = SubmissionStatus::Disqualified;
        $submissaoA->save();
        app(ComputeResults::class)->handle($event);

        $this->assertSame(1, Result::forEvent($event)->count());
        $this->assertNull(Result::forEvent($event)->where('submission_id', $submissaoA->id)->first());
    }

    public function test_a_disqualified_submission_leaves_the_judge_queue_and_cannot_be_evaluated(): void
    {
        $event = Event::factory()->create();
        [$team, $submission] = $this->equipeComEnvio($event);
        $jurado = User::factory()->create(['email_verified_at' => now()]);
        $jurado->assignRole(Role::Jurado->value);

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submission->id;
        $assignment->assigned_at = now();
        $assignment->save();

        $this->actingAs($jurado)->get(route('jurado.index'))
            ->assertInertia(fn ($page) => $page->where('progresso.total', 1));
        $this->actingAs($jurado)->get(route('jurado.avaliar.show', $submission))->assertOk();

        $this->actingAs($this->organizador())->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO]);

        $this->actingAs($jurado)->get(route('jurado.index'))
            ->assertInertia(fn ($page) => $page->where('progresso.total', 0));
        $this->actingAs($jurado)->get(route('jurado.avaliar.show', $submission))->assertForbidden();
    }

    public function test_the_submission_detail_offers_the_right_action_for_the_team_status(): void
    {
        $event = Event::factory()->create();
        [$team, $submission] = $this->equipeComEnvio($event);
        $organizador = $this->organizador();

        $this->actingAs($organizador)->get(route('painel.submissions.show', $submission))
            ->assertInertia(fn ($page) => $page
                ->where('submissao.equipe.pode_desclassificar', true)
                ->where('submissao.equipe.pode_reabilitar', false)
                ->where('submissao.equipe.decisao', null));

        $this->actingAs($organizador)->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO]);

        $this->actingAs($organizador)->get(route('painel.submissions.show', $submission))
            ->assertInertia(fn ($page) => $page
                ->where('submissao.equipe.pode_desclassificar', false)
                ->where('submissao.equipe.pode_reabilitar', true)
                ->where('submissao.equipe.decisao.motivo', self::MOTIVO)
                ->where('submissao.equipe.decisao.acao', 'Equipe desclassificada'));
    }

    /** Achado na homologação: com uma equipe só, desclassificar esvazia o resultado e reabilitar não o refazia. */
    public function test_reinstating_the_only_competing_team_brings_the_result_back(): void
    {
        $event = Event::factory()->create();
        [$team, $submission] = $this->equipeComEnvio($event);
        app(ComputeResults::class)->handle($event);
        $organizador = $this->organizador();

        $this->actingAs($organizador)->post(route('painel.teams.disqualify', $team), ['motivo' => self::MOTIVO]);
        $this->assertSame(0, Result::forEvent($event)->count());

        $this->actingAs($organizador)->post(route('painel.teams.reinstate', $team), ['motivo' => 'Decisão revista pela comissão.']);

        $this->assertNotNull(Result::forEvent($event)->where('submission_id', $submission->id)->first());
    }
}
