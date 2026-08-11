<?php

namespace Tests\Feature\Public;

use App\Enums\Role;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\PopularVote;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionShowcaseTest extends TestCase
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
        $user->assignRole(Role::Participante->value);
        EventRegistration::factory()->for($event)->for($user)->create();

        return $user;
    }

    private function submissaoEnviada(Event $event): Submission
    {
        $team = Team::factory()->for($event)->create();

        return Submission::factory()->for($event)->for($team)->enviada()->create();
    }

    public function test_lists_submitted_projects(): void
    {
        $event = Event::factory()->create();
        $submissao = $this->submissaoEnviada($event);

        $response = $this->get(route('projetos.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('submissoes', 1)
            ->where('submissoes.0.titulo', $submissao->title)
        );
    }

    public function test_a_guest_cannot_vote_but_sees_the_showcase(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $this->submissaoEnviada($event);

        $response = $this->get(route('projetos.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('votacao_aberta', true)
            ->where('pode_votar', false)
        );
    }

    public function test_an_unregistered_user_cannot_vote(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);

        $response = $this->actingAs($user)->get(route('projetos.index'));

        $response->assertInertia(fn ($page) => $page->where('pode_votar', false));
    }

    public function test_a_registered_user_can_vote_while_the_window_is_open(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $user = $this->inscrito($event);

        $response = $this->actingAs($user)->get(route('projetos.index'));

        $response->assertInertia(fn ($page) => $page->where('pode_votar', true));
    }

    public function test_reflects_the_submission_the_user_already_voted_for(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $user = $this->inscrito($event);
        $submissao = $this->submissaoEnviada($event);
        PopularVote::factory()->for($event)->for($submissao)->for($user)->create();

        $response = $this->actingAs($user)->get(route('projetos.index'));

        $response->assertInertia(fn ($page) => $page->where('ja_votou_em', $submissao->id));
    }
}
