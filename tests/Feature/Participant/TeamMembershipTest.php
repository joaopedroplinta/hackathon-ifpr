<?php

namespace Tests\Feature\Participant;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMembershipTest extends TestCase
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
     * @return array{0: Team, 1: TeamMember, 2: TeamMember}
     */
    private function equipeComLiderEIntegrante(Event $event): array
    {
        $lider = $this->inscrito($event);
        $integrante = $this->inscrito($event);

        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);

        $mLider = TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();
        $mIntegrante = TeamMember::factory()->for($event)->for($team)->for($integrante)->create();

        return [$team, $mLider, $mIntegrante];
    }

    public function test_a_member_can_leave_the_team(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, , $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mIntegrante->user)
            ->delete(route('teams.leave', $mIntegrante))
            ->assertRedirect(route('teams.show'));

        $this->assertSame(TeamMemberStatus::Left, $mIntegrante->fresh()->status);
        $this->assertNotNull($mIntegrante->fresh()->left_at);
        $this->assertSame(1, $team->fresh()->activeMemberCount());
    }

    public function test_leaving_frees_the_person_to_join_another_team(): void
    {
        $event = Event::factory()->aberto()->create();
        [, , $mIntegrante] = $this->equipeComLiderEIntegrante($event);
        $user = $mIntegrante->user;

        $this->actingAs($user)->delete(route('teams.leave', $mIntegrante));

        $outra = Team::factory()->for($event)->create(['leader_id' => $this->inscrito($event)->id]);

        $this->actingAs($user)
            ->post(route('teams.join.store'), ['invite_code' => $outra->invite_code])
            ->assertRedirect(route('teams.show'));

        $this->assertTrue($outra->fresh()->hasMember($user));
    }

    public function test_the_leader_cannot_leave_while_others_remain(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $mLider] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mLider->user)
            ->delete(route('teams.leave', $mLider))
            ->assertForbidden();

        $this->assertSame(
            TeamMemberStatus::Active,
            $mLider->fresh()->status,
            'Líder que sai sem passar o bastão deixa a equipe órfã.'
        );
        $this->assertSame(2, $team->fresh()->activeMemberCount());
    }

    public function test_the_sole_member_leaving_dissolves_the_team(): void
    {
        $event = Event::factory()->aberto()->create();
        $lider = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);
        $membership = TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();

        $this->actingAs($lider)
            ->delete(route('teams.leave', $membership))
            ->assertRedirect(route('teams.show'));

        $this->assertSoftDeleted($team);
    }

    public function test_nobody_can_leave_on_behalf_of_someone_else(): void
    {
        $event = Event::factory()->aberto()->create();
        [, , $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $estranho = $this->inscrito($event);

        $this->actingAs($estranho)
            ->delete(route('teams.leave', $mIntegrante))
            ->assertForbidden();

        $this->assertSame(TeamMemberStatus::Active, $mIntegrante->fresh()->status);
    }

    public function test_leaving_is_refused_after_the_deadline(): void
    {
        $event = Event::factory()->inscricoesFechadas()->create();
        [, , $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mIntegrante->user)
            ->delete(route('teams.leave', $mIntegrante))
            ->assertForbidden();

        $this->assertSame(TeamMemberStatus::Active, $mIntegrante->fresh()->status);
    }

    public function test_the_leader_removes_a_member(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $mLider, $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mLider->user)
            ->delete(route('teams.members.remove', $mIntegrante))
            ->assertRedirect(route('teams.show'));

        $this->assertSame(TeamMemberStatus::Left, $mIntegrante->fresh()->status);
        $this->assertSame(1, $team->fresh()->activeMemberCount());
    }

    public function test_a_member_cannot_remove_another_member(): void
    {
        $event = Event::factory()->aberto()->create();
        [, $mLider, $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mIntegrante->user)
            ->delete(route('teams.members.remove', $mLider))
            ->assertForbidden();

        $this->assertSame(TeamMemberStatus::Active, $mLider->fresh()->status);
    }

    public function test_the_leader_cannot_remove_themselves(): void
    {
        $event = Event::factory()->aberto()->create();
        [, $mLider] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mLider->user)
            ->delete(route('teams.members.remove', $mLider))
            ->assertForbidden();

        $this->assertSame(TeamMemberStatus::Active, $mLider->fresh()->status);
    }

    public function test_the_leader_transfers_leadership(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $mLider, $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mLider->user)
            ->patch(route('teams.leadership.update', $team), ['membership_id' => $mIntegrante->id])
            ->assertRedirect(route('teams.show'));

        $this->assertSame($mIntegrante->user_id, $team->fresh()->leader_id);
        $this->assertSame(TeamMemberRole::Leader, $mIntegrante->fresh()->role);
        $this->assertSame(
            TeamMemberRole::Member,
            $mLider->fresh()->role,
            'O líder anterior continua na equipe, como integrante.'
        );
    }

    public function test_after_transferring_the_old_leader_can_leave(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $mLider, $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mLider->user)
            ->patch(route('teams.leadership.update', $team), ['membership_id' => $mIntegrante->id]);

        $this->actingAs($mLider->user)
            ->delete(route('teams.leave', $mLider))
            ->assertRedirect(route('teams.show'));

        $this->assertSame(TeamMemberStatus::Left, $mLider->fresh()->status);
        $this->assertSame($mIntegrante->user_id, $team->fresh()->leader_id);
    }

    public function test_a_member_cannot_transfer_leadership(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $mLider, $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $this->actingAs($mIntegrante->user)
            ->patch(route('teams.leadership.update', $team), ['membership_id' => $mIntegrante->id])
            ->assertForbidden();

        $this->assertSame($mLider->user_id, $team->fresh()->leader_id);
    }

    public function test_leadership_cannot_go_to_someone_from_another_team(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $mLider] = $this->equipeComLiderEIntegrante($event);

        $outraEquipe = Team::factory()->for($event)->create(['leader_id' => $this->inscrito($event)->id]);
        $deFora = TeamMember::factory()->for($event)->for($outraEquipe)->for($this->inscrito($event))->create();

        $this->actingAs($mLider->user)
            ->patch(route('teams.leadership.update', $team), ['membership_id' => $deFora->id])
            ->assertSessionHasErrors('membership_id');

        $this->assertSame($mLider->user_id, $team->fresh()->leader_id);
    }

    public function test_leadership_cannot_be_transferred_to_someone_who_left(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $mLider, $mIntegrante] = $this->equipeComLiderEIntegrante($event);

        $mIntegrante->forceFill([
            'status' => TeamMemberStatus::Left,
            'left_at' => now(),
        ])->save();

        $this->actingAs($mLider->user)
            ->patch(route('teams.leadership.update', $team), ['membership_id' => $mIntegrante->id])
            ->assertForbidden();

        $this->assertSame($mLider->user_id, $team->fresh()->leader_id);
    }
}
