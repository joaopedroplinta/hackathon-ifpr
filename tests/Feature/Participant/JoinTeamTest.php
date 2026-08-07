<?php

namespace Tests\Feature\Participant;

use App\Actions\Teams\JoinTeamByCode;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JoinTeamTest extends TestCase
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

    public function test_a_registered_user_joins_a_team_with_a_valid_code(): void
    {
        $event = Event::factory()->aberto()->create();
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id, 'invite_code' => 'AS3DYP']);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $user = $this->inscrito($event);

        $response = $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'AS3DYP']);

        $response->assertRedirect(route('teams.show'));

        $membership = TeamMember::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($team->id, $membership->team_id);
        $this->assertSame(TeamMemberRole::Member, $membership->role);
        $this->assertSame(TeamMemberStatus::Active, $membership->status);
    }

    public function test_the_code_is_accepted_lowercase_and_with_surrounding_spaces(): void
    {
        $event = Event::factory()->aberto()->create();
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id, 'invite_code' => 'AS3DYP']);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => ' as3dyp '])
            ->assertRedirect(route('teams.show'));

        $this->assertTrue($team->fresh()->hasMember($user));
    }

    public function test_an_unknown_code_is_rejected_as_a_validation_error(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'ZZZZZZ'])
            ->assertSessionHasErrors('invite_code');

        $this->assertSame(0, TeamMember::where('user_id', $user->id)->count());
    }

    public function test_a_code_from_another_event_is_rejected_the_same_way_as_an_unknown_code(): void
    {
        $outro = Event::factory()->create(['slug' => 'edicao-anterior']);
        $team = Team::factory()->for($outro)->create(['invite_code' => 'OLDCOD']);

        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'OLDCOD'])
            ->assertSessionHasErrors('invite_code');

        $this->assertFalse($team->fresh()->hasMember($user));
    }

    public function test_a_user_who_is_not_registered_cannot_join(): void
    {
        $event = Event::factory()->aberto()->create();
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id, 'invite_code' => 'AS3DYP']);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'AS3DYP'])
            ->assertForbidden();

        $this->assertFalse($team->fresh()->hasMember($user));
    }

    public function test_joining_is_blocked_after_the_registration_deadline(): void
    {
        $event = Event::factory()->inscricoesFechadas()->create();
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id, 'invite_code' => 'AS3DYP']);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'AS3DYP'])
            ->assertForbidden();

        $this->assertFalse($team->fresh()->hasMember($user));
    }

    public function test_a_user_who_already_has_a_team_cannot_join_another(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->inscrito($event);
        $minhaEquipe = Team::factory()->for($event)->create(['leader_id' => $user->id]);
        TeamMember::factory()->for($event)->for($minhaEquipe)->for($user)->lider()->create();

        $outroLider = $this->inscrito($event);
        $outraEquipe = Team::factory()->for($event)->create(['leader_id' => $outroLider->id, 'invite_code' => 'AS3DYP']);
        TeamMember::factory()->for($event)->for($outraEquipe)->for($outroLider)->lider()->create();

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'AS3DYP'])
            ->assertForbidden();

        $this->assertSame(1, TeamMember::where('user_id', $user->id)->count());
    }

    public function test_a_full_team_cannot_receive_a_new_member(): void
    {
        $event = Event::factory()->aberto()->create(['max_team_size' => 1]);
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id, 'invite_code' => 'AS3DYP']);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'AS3DYP'])
            ->assertForbidden();

        $this->assertFalse($team->fresh()->hasMember($user));
    }

    public function test_a_disqualified_team_cannot_receive_a_new_member(): void
    {
        $event = Event::factory()->aberto()->create();
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->desclassificada()->create([
            'leader_id' => $lider->id,
            'invite_code' => 'AS3DYP',
        ]);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $user = $this->inscrito($event);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => 'AS3DYP'])
            ->assertForbidden();

        $this->assertFalse($team->fresh()->hasMember($user));
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->post(route('teams.join.store'), ['invite_code' => 'AS3DYP'])
            ->assertRedirect(route('login'));
    }

    public function test_the_join_page_renders_for_a_registered_user(): void
    {
        $event = Event::factory()->aberto()->create();

        $this->actingAs($this->inscrito($event))
            ->get(route('teams.join.create'))
            ->assertOk();
    }

    public function test_the_policy_refuses_a_full_team_over_http(): void
    {
        $event = Event::factory()->aberto()->create(['max_team_size' => 2]);
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $primeiro = $this->inscrito($event);
        $this->actingAs($primeiro)
            ->post(route('teams.join.store'), ['invite_code' => $team->invite_code])
            ->assertRedirect(route('teams.show'));

        $this->actingAs($this->inscrito($event))
            ->post(route('teams.join.store'), ['invite_code' => $team->invite_code])
            ->assertForbidden();

        $this->assertSame(2, $team->fresh()->activeMemberCount());
    }

    /**
     * A Policy roda antes de qualquer gravacao, entao duas pessoas usando o
     * mesmo codigo ao mesmo tempo podem passar as duas por ela. A recontagem
     * sob lockForUpdate, dentro da transacao, e a ultima linha de defesa --
     * e um teste sequencial nao consegue provocar a concorrencia, entao aqui
     * a Action e chamada direto, no estado em que a corrida a deixaria.
     */
    public function test_the_action_refuses_to_overflow_the_team_even_if_the_policy_passed(): void
    {
        $event = Event::factory()->aberto()->create(['max_team_size' => 2]);
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);

        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();
        TeamMember::factory()->for($event)->for($team)->for($this->inscrito($event))->create();

        $atrasado = $this->inscrito($event);

        $this->expectException(ValidationException::class);

        try {
            app(JoinTeamByCode::class)->handle($event, $atrasado, $team->invite_code);
        } finally {
            $this->assertSame(
                2,
                $team->fresh()->activeMemberCount(),
                'A equipe nao pode ultrapassar max_team_size.'
            );
        }
    }
}
