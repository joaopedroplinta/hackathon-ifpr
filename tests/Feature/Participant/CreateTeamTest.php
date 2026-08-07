<?php

namespace Tests\Feature\Participant;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTeamTest extends TestCase
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

    public function test_a_registered_user_creates_a_team_and_becomes_its_leader(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);

        $response = $this->actingAs($user)->post(route('teams.store'), [
            'name' => 'Os Devs',
            'description' => 'Vamos resolver filas do refeitório.',
        ]);

        $response->assertRedirect(route('teams.show'));

        $team = Team::firstOrFail();

        $this->assertSame('Os Devs', $team->name);
        $this->assertSame($user->id, $team->leader_id);
        $this->assertSame($event->id, $team->event_id);

        $membership = TeamMember::firstOrFail();

        $this->assertSame($user->id, $membership->user_id);
        $this->assertSame(TeamMemberRole::Leader, $membership->role);
        $this->assertSame(TeamMemberStatus::Active, $membership->status);
    }

    public function test_the_invite_code_is_readable_and_not_derived_from_the_id(): void
    {
        $event = Event::factory()->aberto()->create();

        $this->actingAs($this->inscrito($event))
            ->post(route('teams.store'), ['name' => 'Equipe A']);

        $code = Team::firstOrFail()->invite_code;

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $code);
        $this->assertStringNotContainsString('0', $code, 'Zero e a letra O se confundem ao digitar.');
        $this->assertStringNotContainsString('1', $code);
    }

    public function test_a_user_who_is_not_registered_cannot_create_a_team(): void
    {
        Event::factory()->aberto()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('teams.store'), ['name' => 'Sem Inscrição'])
            ->assertForbidden();

        $this->assertSame(0, Team::count());
    }

    public function test_a_team_cannot_be_created_after_the_deadline(): void
    {
        $event = Event::factory()->inscricoesFechadas()->create();
        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->post(route('teams.store'), ['name' => 'Atrasados'])
            ->assertForbidden();

        $this->assertSame(0, Team::count());
    }

    public function test_a_user_cannot_lead_two_teams(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);

        $this->actingAs($user)->post(route('teams.store'), ['name' => 'Primeira']);

        $this->actingAs($user)
            ->post(route('teams.store'), ['name' => 'Segunda'])
            ->assertForbidden();

        $this->assertSame(1, Team::count());
    }

    public function test_the_database_refuses_a_second_active_membership_in_the_same_event(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);
        $outra = Team::factory()->for($event)->create(['leader_id' => $user->id]);
        $terceira = Team::factory()->for($event)->create(['leader_id' => $user->id]);

        TeamMember::factory()->for($event)->for($outra)->for($user)->create();

        // Índice parcial do Postgres. É o que segura a corrida do duplo
        // clique, que a checagem da aplicação não pega.
        $this->expectException(UniqueConstraintViolationException::class);

        TeamMember::factory()->for($event)->for($terceira)->for($user)->create();
    }

    public function test_leaving_a_team_frees_the_user_to_join_another(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);
        $antiga = Team::factory()->for($event)->create(['leader_id' => $user->id]);
        $nova = Team::factory()->for($event)->create(['leader_id' => $user->id]);

        TeamMember::factory()->for($event)->for($antiga)->for($user)->saiu()->create();

        // O índice só vale para status = active, então isto tem de passar.
        TeamMember::factory()->for($event)->for($nova)->for($user)->create();

        $this->assertSame(2, TeamMember::count());
    }

    public function test_two_teams_cannot_share_a_name_in_the_same_event(): void
    {
        $event = Event::factory()->aberto()->create();
        Team::factory()->for($event)->create(['name' => 'Os Devs']);

        $this->actingAs($this->inscrito($event))
            ->post(route('teams.store'), ['name' => 'Os Devs'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Team::count());
    }

    public function test_the_same_name_is_allowed_in_a_different_event(): void
    {
        $anterior = Event::factory()->create(['edition' => 1, 'slug' => 'edicao-1']);
        Team::factory()->for($anterior)->create(['name' => 'Os Devs']);

        $atual = Event::factory()->aberto()->create(['edition' => 2, 'slug' => 'edicao-2']);

        $this->actingAs($this->inscrito($atual))
            ->post(route('teams.store'), ['name' => 'Os Devs'])
            ->assertRedirect(route('teams.show'));

        $this->assertSame(2, Team::count());
    }

    public function test_a_track_from_another_event_is_rejected(): void
    {
        $outro = Event::factory()->create(['slug' => 'outro-evento']);
        $trilhaDeOutro = Track::factory()->for($outro)->create();

        $event = Event::factory()->aberto()->create();

        $this->actingAs($this->inscrito($event))
            ->post(route('teams.store'), [
                'name' => 'Trilha Errada',
                'track_id' => $trilhaDeOutro->id,
            ])
            ->assertSessionHasErrors('track_id');
    }

    public function test_a_short_name_is_rejected(): void
    {
        $event = Event::factory()->aberto()->create();

        $this->actingAs($this->inscrito($event))
            ->post(route('teams.store'), ['name' => 'ab'])
            ->assertSessionHasErrors('name');
    }

    public function test_the_page_shows_the_empty_state_when_the_user_has_no_team(): void
    {
        $event = Event::factory()->aberto()->create();

        $this->actingAs($this->inscrito($event))
            ->get(route('teams.show'))
            ->assertOk();
    }

    public function test_a_member_of_another_team_cannot_see_it(): void
    {
        $event = Event::factory()->aberto()->create();
        $dono = $this->inscrito($event);
        $estranho = $this->inscrito($event);

        $team = Team::factory()->for($event)->create(['leader_id' => $dono->id]);
        TeamMember::factory()->for($event)->for($team)->for($dono)->lider()->create();

        // O estranho não tem equipe, então cai no estado vazio em vez de
        // enxergar a equipe alheia.
        $this->actingAs($estranho)
            ->get(route('teams.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('equipe/sem-equipe'));
    }
}
