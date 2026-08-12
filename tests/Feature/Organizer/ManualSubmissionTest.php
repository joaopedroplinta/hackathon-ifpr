<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\Event;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualSubmissionTest extends TestCase
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

    private function dadosValidos(Team $team, array $sobrescrever = []): array
    {
        return array_merge([
            'team_id' => $team->id,
            'title' => 'Projeto recebido por e-mail',
            'summary' => 'Resumo qualquer',
            'repo_url' => 'https://github.com/equipe/projeto',
            'video_url' => '',
            'original_submitted_at' => now()->toIso8601String(),
            'source' => SubmissionSource::Email->value,
        ], $sobrescrever);
    }

    public function test_staff_records_a_submission_on_behalf_of_a_team(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.submissions.record.store'), $this->dadosValidos($team))
            ->assertRedirect(route('admin.submissions.index'))
            ->assertSessionHas('sucesso');

        $submission = Submission::forEvent($event)->where('team_id', $team->id)->firstOrFail();
        $this->assertSame(SubmissionSource::Email, $submission->source);
        $this->assertNotNull($submission->recorded_by);
        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
    }

    public function test_lists_only_teams_without_a_counted_submission(): void
    {
        $event = Event::factory()->create();
        $pendente = Team::factory()->for($event)->create(['name' => 'Equipe Pendente']);
        $jaEnviou = Team::factory()->for($event)->create(['name' => 'Equipe Já Enviou']);
        Submission::factory()->for($event)->for($jaEnviou)->enviada()->create();

        $response = $this->actingAs($this->organizador())->get(route('admin.submissions.record.create'));

        $response->assertInertia(fn ($page) => $page
            ->has('equipes', 1)
            ->where('equipes.0.nome', 'Equipe Pendente')
        );
    }

    public function test_does_not_overwrite_a_team_that_already_submitted(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'Original']);

        $this->actingAs($this->organizador())
            ->post(route('admin.submissions.record.store'), $this->dadosValidos($team))
            ->assertSessionHasErrors('team_id');

        $this->assertSame('Original', Submission::forEvent($event)->where('team_id', $team->id)->firstOrFail()->title);
    }

    public function test_repo_url_is_required(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.submissions.record.store'), $this->dadosValidos($team, ['repo_url' => '']))
            ->assertSessionHasErrors('repo_url');
    }

    public function test_a_participant_cannot_record_submissions(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();

        $this->actingAs($this->participante())
            ->get(route('admin.submissions.record.create'))
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->post(route('admin.submissions.record.store'), $this->dadosValidos($team))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.submissions.record.create'))->assertRedirect(route('login'));
    }
}
