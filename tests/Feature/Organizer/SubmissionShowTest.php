<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SubmissionShowTest extends TestCase
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

    public function test_staff_sees_the_full_detail_with_version_history_and_files(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create(['name' => 'Equipe Alfa']);
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $autor = User::factory()->create(['name' => 'Maria Autora']);
        SubmissionVersion::factory()->for($submission)->for($autor, 'author')->create([
            'version' => 1,
            'payload' => ['title' => 'Alerta', 'status' => 'submitted', 'source' => 'web'],
        ]);

        SubmissionFile::factory()->for($submission)->create(['original_name' => 'pitch.pdf']);

        $this->actingAs($this->organizador())
            ->get(route('painel.submissions.show', $submission))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('admin/submissoes/mostrar')
                    ->where('submissao.id', $submission->id)
                    ->where('submissao.equipe.nome', 'Equipe Alfa')
                    ->has('versoes', 1)
                    ->where('versoes.0.autor', 'Maria Autora')
                    // Enum nunca aparece cru na tela -- .claude/rules/frontend.md.
                    ->where('versoes.0.payload.status', 'Enviado')
                    ->where('versoes.0.payload.source', 'Sistema')
                    ->has('arquivos', 1)
                    ->where('arquivos.0.nome', 'pitch.pdf')
            );
    }

    /** SubmissionPolicy::view: participante de outra equipe não enxerga. */
    public function test_a_participant_from_another_team_gets_forbidden(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $outraEquipe = Team::factory()->for($event)->create();
        $intruso = User::factory()->create(['email_verified_at' => now()]);
        $intruso->assignRole(Role::Participante->value);
        TeamMember::factory()->for($event)->for($outraEquipe)->for($intruso)->create();

        $this->actingAs($intruso)
            ->get(route('painel.submissions.show', $submission))
            ->assertForbidden();
    }

    /** A própria equipe pode ver -- SubmissionPolicy::view não é só para staff. */
    public function test_a_member_of_the_team_can_see_its_own_submission(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $membro = User::factory()->create(['email_verified_at' => now()]);
        $membro->assignRole(Role::Participante->value);
        TeamMember::factory()->for($event)->for($team)->for($membro)->create();

        $this->actingAs($membro)
            ->get(route('painel.submissions.show', $submission))
            ->assertOk();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $this->get(route('painel.submissions.show', $submission))
            ->assertRedirect(route('login'));
    }

    public function test_a_submission_without_any_version_shows_an_empty_history(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $submission = Submission::factory()->for($event)->for($team)->create();

        $this->actingAs($this->organizador())
            ->get(route('painel.submissions.show', $submission))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('versoes', 0));
    }
}
