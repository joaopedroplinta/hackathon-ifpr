<?php

namespace Tests\Feature\Participant;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function inscrito(Event $event): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        EventRegistration::factory()->for($event)->for($user)->create();

        return $user;
    }

    /**
     * @return array{0: Team, 1: User}
     */
    private function equipeComLider(Event $event): array
    {
        $leader = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $leader->id]);
        TeamMember::factory()->for($event)->for($team)->for($leader)->lider()->create();

        return [$team, $leader];
    }

    /**
     * @return array<string, string>
     */
    private function projetoValido(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Painel de alertas de enchente',
            'summary' => 'Avisa moradores de área de risco quando o rio passa do nível de atenção.',
            'repo_url' => 'https://github.com/equipe/alerta',
        ], $overrides);
    }

    public function test_the_team_submits_within_the_deadline_and_it_counts_as_submitted(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addHours(3)]);
        [$team, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('submissions.submit'), $this->projetoValido())
            ->assertRedirect(route('submissions.show'))
            ->assertSessionHas('sucesso');

        $submission = Submission::firstOrFail();

        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
        $this->assertSame($team->id, $submission->team_id);
        $this->assertSame($event->id, $submission->event_id);
        $this->assertSame(SubmissionSource::Web, $submission->source);
        $this->assertSame(1, $submission->current_version);
        $this->assertNotNull($submission->submitted_at);
    }

    /**
     * O critério de pronto da semana 3: envio depois do prazo entra como
     * `late`, nunca é recusado em silêncio (PLANO.md, Anexo A).
     */
    public function test_a_submission_after_the_deadline_is_accepted_as_late(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->subMinute()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('submissions.submit'), $this->projetoValido())
            ->assertRedirect(route('submissions.show'))
            // Fora do prazo é avisado na hora, não descoberto depois.
            ->assertSessionHas('erro');

        $submission = Submission::firstOrFail();

        $this->assertSame(SubmissionStatus::Late, $submission->status);
        $this->assertSame(1, $submission->current_version);
        $this->assertTrue($submission->status->countsForEvaluation());
    }

    public function test_the_deadline_comes_from_the_server_not_from_the_request(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->subHour()]);
        [, $leader] = $this->equipeComLider($event);

        // A equipe tenta mandar o próprio horário junto. Não muda nada.
        $this->actingAs($leader)->post(route('submissions.submit'), $this->projetoValido([
            'submitted_at' => now()->subDay()->toIso8601String(),
            'status' => SubmissionStatus::Submitted->value,
        ]))->assertRedirect();

        $submission = Submission::firstOrFail();

        $this->assertSame(SubmissionStatus::Late, $submission->status);
        $this->assertTrue($submission->submitted_at->isAfter(now()->subMinute()));
    }

    public function test_a_draft_is_saved_without_becoming_a_submission(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('submissions.save'), ['title' => 'Ainda pensando'])
            ->assertRedirect(route('submissions.show'))
            ->assertSessionHas('sucesso');

        $submission = Submission::firstOrFail();

        $this->assertSame(SubmissionStatus::Draft, $submission->status);
        $this->assertSame('Ainda pensando', $submission->title);
        $this->assertNull($submission->submitted_at);
        $this->assertSame(0, $submission->current_version);
        $this->assertFalse($submission->status->countsForEvaluation());
        $this->assertSame(0, SubmissionVersion::count());
    }

    public function test_a_draft_does_not_require_the_fields_the_submission_requires(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('submissions.save'), ['repo_url' => ''])
            ->assertSessionHasNoErrors();

        $this->actingAs($leader)
            ->post(route('submissions.submit'), ['title' => 'Só o título'])
            ->assertSessionHasErrors(['summary', 'repo_url']);

        $this->assertSame(SubmissionStatus::Draft, Submission::firstOrFail()->status);
    }

    public function test_each_submission_creates_a_new_version_without_overwriting_the_previous_one(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        $member = $this->inscrito($event);
        TeamMember::factory()->for($event)->for($team)->for($member)->create();

        $this->actingAs($leader)->post(route('submissions.submit'), $this->projetoValido());
        $this->actingAs($member)->post(route('submissions.submit'), $this->projetoValido([
            'title' => 'Painel de alertas — versão final',
        ]));

        $submission = Submission::firstOrFail();
        $this->assertSame(2, $submission->current_version);
        $this->assertSame(2, $submission->versions()->count());

        // reorder(): a relação já vem ordenada do mais recente para o mais
        // antigo, e orderBy() só somaria um critério secundário.
        $versoes = $submission->versions()->reorder('version')->get();

        $this->assertSame('Painel de alertas de enchente', $versoes[0]->payload['title']);
        $this->assertSame($leader->id, $versoes[0]->created_by);

        $this->assertSame('Painel de alertas — versão final', $versoes[1]->payload['title']);
        $this->assertSame($member->id, $versoes[1]->created_by);
    }

    public function test_any_active_member_can_submit_not_only_the_leader(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team] = $this->equipeComLider($event);

        $member = $this->inscrito($event);
        TeamMember::factory()->for($event)->for($team)->for($member)->create();

        $this->actingAs($member)
            ->post(route('submissions.submit'), $this->projetoValido())
            ->assertRedirect(route('submissions.show'));

        $this->assertSame(SubmissionStatus::Submitted, Submission::firstOrFail()->status);
    }

    public function test_someone_who_already_left_the_team_cannot_submit(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team] = $this->equipeComLider($event);

        $exMembro = $this->inscrito($event);
        TeamMember::factory()->for($event)->for($team)->for($exMembro)->saiu()->create();

        $this->actingAs($exMembro)
            ->post(route('submissions.submit'), $this->projetoValido())
            ->assertForbidden();

        $this->assertSame(0, Submission::count());
    }

    public function test_a_member_of_another_team_cannot_touch_this_submission(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submissions.submit'), $this->projetoValido());

        [, $outroLider] = $this->equipeComLider($event);

        $this->actingAs($outroLider)->post(route('submissions.save'), ['title' => 'Roubando a vez']);

        // Cada equipe mexe na própria submissão: a do time original ficou intacta.
        $this->assertSame('Painel de alertas de enchente', $team->submission()->firstOrFail()->title);
        $this->assertSame(2, Submission::count());
    }

    public function test_a_team_that_already_submitted_cannot_resend_after_the_deadline(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addHour()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submissions.submit'), $this->projetoValido())->assertRedirect();

        // O prazo vira depois do envio.
        $event->update(['submission_deadline' => now()->subMinute()]);

        $this->actingAs($leader)
            ->post(route('submissions.submit'), $this->projetoValido(['title' => 'Reenvio tardio']))
            ->assertForbidden();

        $submission = Submission::firstOrFail();
        $this->assertSame('Painel de alertas de enchente', $submission->title);
        $this->assertSame(1, $submission->current_version);
    }

    public function test_a_disqualified_team_cannot_submit(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        // forceFill: `status` fica fora do $fillable de propósito -- só o
        // organizador desclassifica, nunca um formulário.
        $team->forceFill(['status' => TeamStatus::Disqualified])->save();

        $this->actingAs($leader)
            ->post(route('submissions.submit'), $this->projetoValido())
            ->assertForbidden();

        $this->assertSame(0, Submission::count());
    }

    public function test_a_user_without_a_team_is_sent_back_to_the_team_page(): void
    {
        $event = Event::factory()->aberto()->create();
        $semEquipe = $this->inscrito($event);

        $this->actingAs($semEquipe)
            ->get(route('submissions.show'))
            ->assertRedirect(route('teams.show'))
            ->assertSessionHas('erro');

        $this->actingAs($semEquipe)
            ->post(route('submissions.submit'), $this->projetoValido())
            ->assertForbidden();
    }

    public function test_a_guest_cannot_reach_the_submission_page(): void
    {
        $this->get(route('submissions.show'))->assertRedirect(route('login'));
    }

    public function test_the_page_shows_the_deadline_and_the_current_submission(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addHours(5)]);
        [$team, $leader] = $this->equipeComLider($event);

        Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'Projeto da casa']);

        $this->actingAs($leader)
            ->get(route('submissions.show'))
            ->assertInertia(fn ($page) => $page
                ->component('submissao/minha')
                ->where('submissao.title', 'Projeto da casa')
                ->where('submissao.status', 'submitted')
                ->where('submissao.status_label', 'Enviado')
                ->where('submissao.foi_enviada', true)
                ->where('prazo.aberto', true)
                ->where('pode_editar', true)
                ->where('equipe.nome', $team->name)
            );
    }

    public function test_the_page_explains_why_editing_is_blocked_after_the_deadline(): void
    {
        $event = Event::factory()->create(['submission_deadline' => now()->subHour()]);
        [$team, $leader] = $this->equipeComLider($event);

        Submission::factory()->for($event)->for($team)->enviada()->create();

        $this->actingAs($leader)
            ->get(route('submissions.show'))
            ->assertInertia(fn ($page) => $page
                ->component('submissao/minha')
                ->where('pode_editar', false)
                ->where('prazo.aberto', false)
                ->whereNot('motivo_bloqueio', null)
            );
    }

    public function test_a_team_that_missed_the_deadline_entirely_can_still_send_it_late(): void
    {
        $event = Event::factory()->create(['submission_deadline' => now()->subHours(2)]);
        [$team, $leader] = $this->equipeComLider($event);

        // Nada foi enviado antes do prazo: a porta continua aberta, marcada.
        $this->actingAs($leader)
            ->get(route('submissions.show'))
            ->assertInertia(fn ($page) => $page->where('pode_editar', true));

        $this->actingAs($leader)->post(route('submissions.submit'), $this->projetoValido())->assertRedirect();

        $this->assertSame(SubmissionStatus::Late, $team->submission()->firstOrFail()->status);
    }

    public function test_the_page_lists_the_team_own_version_history_newest_first(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submissions.submit'), $this->projetoValido());
        $this->actingAs($leader)->post(route('submissions.submit'), $this->projetoValido(['title' => 'Versão final']));

        $this->actingAs($leader)
            ->get(route('submissions.show'))
            ->assertInertia(fn ($page) => $page
                ->has('versoes', 2)
                ->where('versoes.0.versao', 2)
                ->where('versoes.0.autor', $leader->name)
                ->where('versoes.1.versao', 1)
            );
    }

    /** Sem envio nenhum, a lista vem vazia -- é o estado que o componente trata. */
    public function test_the_version_history_is_empty_before_any_submission(): void
    {
        $event = Event::factory()->aberto()->create();
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->get(route('submissions.show'))
            ->assertInertia(fn ($page) => $page->has('versoes', 0));
    }

    /** Escopo por equipe: uma equipe nunca vê o histórico de envio de outra. */
    public function test_a_team_never_sees_another_team_version_history(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $liderA] = $this->equipeComLider($event);
        [, $liderB] = $this->equipeComLider($event);

        $this->actingAs($liderA)->post(route('submissions.submit'), $this->projetoValido());

        $this->actingAs($liderB)
            ->get(route('submissions.show'))
            ->assertInertia(fn ($page) => $page->has('versoes', 0));
    }
}
