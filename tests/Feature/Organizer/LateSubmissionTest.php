<?php

namespace Tests\Feature\Organizer;

use App\Actions\Results\ComputeResults;
use App\Actions\Results\FindResultPendencies;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\Event;
use App\Models\Result;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LateSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private const MOTIVO = 'Queda de rede do campus comprovada pelo chamado da TI.';

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

    private function atrasada(Event $event): Submission
    {
        $team = Team::factory()->for($event)->create();

        return Submission::factory()->for($event)->for($team)->atrasada()->create(['title' => 'Projeto atrasado']);
    }

    public function test_staff_accepts_a_late_submission_with_a_reason(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->atrasada($event);
        $organizador = $this->organizador();

        $this->actingAs($organizador)
            ->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'aceitar', 'motivo' => self::MOTIVO])
            ->assertRedirect(route('painel.submissions.show', $submissao))
            ->assertSessionHas('sucesso');

        $this->assertSame(SubmissionStatus::LateAccepted, $submissao->fresh()->status);

        $log = Activity::where('description', 'Envio fora do prazo aceito')->first();
        $this->assertNotNull($log);
        $this->assertSame($organizador->id, $log->causer_id);
        $this->assertSame($submissao->id, $log->subject_id);
        $this->assertSame(self::MOTIVO, $log->properties['motivo']);
        $this->assertSame('late', $log->properties['de']);
        $this->assertSame('late_accepted', $log->properties['para']);
    }

    public function test_staff_rejects_a_late_submission_with_a_reason(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->atrasada($event);

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'recusar', 'motivo' => self::MOTIVO])
            ->assertSessionHas('sucesso');

        $this->assertSame(SubmissionStatus::LateRejected, $submissao->fresh()->status);
        $this->assertNotNull(Activity::where('description', 'Envio fora do prazo recusado')->first());
    }

    public function test_a_decision_can_be_revised_and_both_are_logged(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->atrasada($event);
        $organizador = $this->organizador();

        $this->actingAs($organizador)->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'recusar', 'motivo' => self::MOTIVO]);
        $this->actingAs($organizador)->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'aceitar', 'motivo' => 'Revisado após conversa com a equipe.']);

        $this->assertSame(SubmissionStatus::LateAccepted, $submissao->fresh()->status);
        $this->assertSame(1, Activity::where('description', 'Envio fora do prazo recusado')->count());
        $this->assertSame(1, Activity::where('description', 'Envio fora do prazo aceito')->count());
    }

    public function test_a_reason_with_enough_detail_is_required(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->atrasada($event);

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'aceitar', 'motivo' => 'curto'])
            ->assertSessionHasErrors('motivo');

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'talvez', 'motivo' => self::MOTIVO])
            ->assertSessionHasErrors('decisao');

        $this->assertSame(SubmissionStatus::Late, $submissao->fresh()->status);
    }

    public function test_a_participant_cannot_decide(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->atrasada($event);
        $participante = User::factory()->create(['email_verified_at' => now()]);
        $participante->assignRole(Role::Participante->value);

        $this->actingAs($participante)
            ->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'aceitar', 'motivo' => self::MOTIVO])
            ->assertForbidden();

        $this->assertSame(SubmissionStatus::Late, $submissao->fresh()->status);
    }

    public function test_only_a_late_submission_can_be_decided(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $noPrazo = Submission::factory()->for($event)->for($team)->enviada()->create();

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $noPrazo), ['decisao' => 'recusar', 'motivo' => self::MOTIVO])
            ->assertForbidden();

        $this->assertSame(SubmissionStatus::Submitted, $noPrazo->fresh()->status);
    }

    public function test_a_submission_from_another_edition_cannot_be_decided(): void
    {
        Event::factory()->create(['edition' => 2]);
        $antigo = Event::factory()->create(['edition' => 1]);
        $submissao = $this->atrasada($antigo);

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'aceitar', 'motivo' => self::MOTIVO])
            ->assertNotFound();

        $this->assertSame(SubmissionStatus::Late, $submissao->fresh()->status);
    }

    public function test_the_detail_page_offers_the_decision_and_shows_the_last_one(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->atrasada($event);
        $organizador = $this->organizador();

        $this->actingAs($organizador)
            ->get(route('painel.submissions.show', $submissao))
            ->assertInertia(fn ($page) => $page
                ->where('submissao.pode_decidir_atraso', true)
                ->where('submissao.decisao_atraso', null));

        $this->actingAs($organizador)->post(route('painel.submissions.late-decision', $submissao), ['decisao' => 'recusar', 'motivo' => self::MOTIVO]);

        $this->actingAs($organizador)
            ->get(route('painel.submissions.show', $submissao))
            ->assertInertia(fn ($page) => $page
                ->where('submissao.pode_decidir_atraso', true)
                ->where('submissao.decisao_atraso.motivo', self::MOTIVO)
                ->where('submissao.decisao_atraso.quem', $organizador->name));
    }

    public function test_a_late_submission_stays_out_of_the_results_until_accepted(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        Submission::factory()->for($event)->for($team)->enviada()->create();
        $atrasada = $this->atrasada($event);

        app(ComputeResults::class)->handle($event);
        $this->assertSame(1, Result::forEvent($event)->count());
        $this->assertNull(Result::forEvent($event)->where('submission_id', $atrasada->id)->first());

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $atrasada), ['decisao' => 'aceitar', 'motivo' => self::MOTIVO]);

        app(ComputeResults::class)->handle($event);
        $this->assertSame(2, Result::forEvent($event)->count());
    }

    public function test_a_rejected_late_submission_never_enters_the_results(): void
    {
        $event = Event::factory()->create();
        $atrasada = $this->atrasada($event);

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $atrasada), ['decisao' => 'recusar', 'motivo' => self::MOTIVO]);

        app(ComputeResults::class)->handle($event);

        $this->assertSame(0, Result::forEvent($event)->count());
    }

    public function test_the_results_pendencies_flag_late_submissions_without_a_decision(): void
    {
        $event = Event::factory()->create();
        $atrasada = $this->atrasada($event);

        $pendencias = app(FindResultPendencies::class)->handle($event);
        $this->assertSame(['Projeto atrasado'], $pendencias['atrasados_sem_decisao']);

        $this->actingAs($this->organizador())
            ->post(route('painel.submissions.late-decision', $atrasada), ['decisao' => 'aceitar', 'motivo' => self::MOTIVO]);

        $this->assertSame([], app(FindResultPendencies::class)->handle($event)['atrasados_sem_decisao']);
    }
}
