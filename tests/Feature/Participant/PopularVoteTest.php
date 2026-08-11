<?php

namespace Tests\Feature\Participant;

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

class PopularVoteTest extends TestCase
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

    public function test_a_registered_user_votes_within_the_window(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $user = $this->inscrito($event);
        $submissao = $this->submissaoEnviada($event);

        $this->actingAs($user)
            ->post(route('votos.store'), ['submission_id' => $submissao->id])
            ->assertRedirect()
            ->assertSessionHas('sucesso');

        $this->assertSame(1, PopularVote::count());
        $this->assertSame($user->id, PopularVote::first()->user_id);
    }

    public function test_voting_twice_is_blocked_by_the_database_unique_constraint(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $user = $this->inscrito($event);
        $primeiraSubmissao = $this->submissaoEnviada($event);
        $segundaSubmissao = $this->submissaoEnviada($event);

        $this->actingAs($user)->post(route('votos.store'), ['submission_id' => $primeiraSubmissao->id]);

        $this->actingAs($user)
            ->post(route('votos.store'), ['submission_id' => $segundaSubmissao->id])
            ->assertSessionHas('erro');

        $this->assertSame(1, PopularVote::count());
        $this->assertSame($primeiraSubmissao->id, PopularVote::first()->submission_id);
    }

    public function test_an_unregistered_user_cannot_vote(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);
        $submissao = $this->submissaoEnviada($event);

        $this->actingAs($user)
            ->post(route('votos.store'), ['submission_id' => $submissao->id])
            ->assertForbidden();

        $this->assertSame(0, PopularVote::count());
    }

    public function test_voting_outside_the_window_is_blocked(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDays(5),
            'voting_closes_at' => now()->subDay(),
        ]);
        $user = $this->inscrito($event);
        $submissao = $this->submissaoEnviada($event);

        $this->actingAs($user)
            ->post(route('votos.store'), ['submission_id' => $submissao->id])
            ->assertForbidden();

        $this->assertSame(0, PopularVote::count());
    }

    public function test_voting_is_closed_by_default_when_no_window_is_configured(): void
    {
        $event = Event::factory()->create(['voting_opens_at' => null, 'voting_closes_at' => null]);
        $user = $this->inscrito($event);
        $submissao = $this->submissaoEnviada($event);

        $this->actingAs($user)
            ->post(route('votos.store'), ['submission_id' => $submissao->id])
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $event = Event::factory()->create([
            'voting_opens_at' => now()->subDay(),
            'voting_closes_at' => now()->addDay(),
        ]);
        $submissao = $this->submissaoEnviada($event);

        $this->post(route('votos.store'), ['submission_id' => $submissao->id])
            ->assertRedirect(route('login'));
    }
}
