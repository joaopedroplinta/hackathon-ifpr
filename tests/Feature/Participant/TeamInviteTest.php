<?php

namespace Tests\Feature\Participant;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamInviteNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamInviteTest extends TestCase
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

    public function test_the_leader_invites_and_the_email_is_queued(): void
    {
        Notification::fake();

        $event = Event::factory()->aberto()->create();
        [$team, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('team-invites.store'), ['email' => 'Novo@Example.com'])
            ->assertRedirect(route('teams.show'));

        $invite = TeamInvite::firstOrFail();
        $this->assertSame('novo@example.com', $invite->email);
        $this->assertSame($team->id, $invite->team_id);
        $this->assertSame($event->id, $invite->event_id);
        $this->assertSame($leader->id, $invite->invited_by);
        $this->assertSame(40, strlen($invite->token));

        Notification::assertSentOnDemand(TeamInviteNotification::class);
    }

    public function test_a_non_leader_cannot_invite(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team] = $this->equipeComLider($event);
        $membro = $this->inscrito($event);
        TeamMember::factory()->for($event)->for($team)->for($membro)->create();

        $this->actingAs($membro)
            ->post(route('team-invites.store'), ['email' => 'novo@example.com'])
            ->assertForbidden();

        $this->assertSame(0, TeamInvite::count());
    }

    public function test_invites_are_refused_after_the_registration_deadline(): void
    {
        $event = Event::factory()->aberto()->create();
        [, $leader] = $this->equipeComLider($event);

        $event->update(['registration_closes_at' => now()->subMinute()]);

        $this->actingAs($leader)
            ->post(route('team-invites.store'), ['email' => 'novo@example.com'])
            ->assertForbidden();

        $this->assertSame(0, TeamInvite::count());
    }

    public function test_a_full_team_cannot_receive_invites(): void
    {
        $event = Event::factory()->aberto()->create(['max_team_size' => 1]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('team-invites.store'), ['email' => 'novo@example.com'])
            ->assertForbidden();

        $this->assertSame(0, TeamInvite::count());
    }

    public function test_inviting_someone_who_already_has_an_active_team_is_refused(): void
    {
        $event = Event::factory()->aberto()->create();
        [, $leader] = $this->equipeComLider($event);

        $outraPessoa = $this->inscrito($event);
        $outraEquipe = Team::factory()->for($event)->create(['leader_id' => $outraPessoa->id]);
        TeamMember::factory()->for($event)->for($outraEquipe)->for($outraPessoa)->lider()->create();

        $this->actingAs($leader)
            ->post(route('team-invites.store'), ['email' => $outraPessoa->email])
            ->assertForbidden();

        $this->assertSame(0, TeamInvite::count());
    }

    public function test_expires_at_never_goes_past_the_registration_deadline(): void
    {
        $event = Event::factory()->create([
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDays(2),
        ]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('team-invites.store'), ['email' => 'novo@example.com'])
            ->assertRedirect();

        $invite = TeamInvite::firstOrFail();

        // Sem o teto do prazo do evento, o default de 7 dias estouraria
        // muito além de registration_closes_at (2 dias).
        $this->assertTrue($invite->expires_at->lessThanOrEqualTo($event->registration_closes_at));
        $this->assertTrue($invite->expires_at->diffInSeconds($event->registration_closes_at) < 5);
    }

    public function test_the_database_refuses_two_pending_invites_for_the_same_email_and_team(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team] = $this->equipeComLider($event);

        TeamInvite::factory()->for($event)->for($team)->create(['email' => 'duplicado@example.com']);

        $this->expectException(UniqueConstraintViolationException::class);

        TeamInvite::factory()->for($event)->for($team)->create(['email' => 'duplicado@example.com']);
    }

    public function test_accepting_creates_the_membership(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $leader] = $this->equipeComLider($event);

        $convidado = User::factory()->create(['email' => 'convidado@example.com', 'email_verified_at' => now()]);
        EventRegistration::factory()->for($event)->for($convidado)->create();

        $invite = TeamInvite::factory()->for($event)->for($team)->create([
            'email' => 'convidado@example.com',
            'invited_by' => $leader->id,
        ]);

        $this->actingAs($convidado)
            ->get(route('team-invites.accept', $invite->token))
            ->assertRedirect(route('teams.show'));

        $this->assertNotNull($invite->fresh()->accepted_at);

        $membership = TeamMember::where('user_id', $convidado->id)->firstOrFail();
        $this->assertSame($team->id, $membership->team_id);
        $this->assertSame(TeamMemberRole::Member, $membership->role);
        $this->assertSame(TeamMemberStatus::Active, $membership->status);
    }

    public function test_accepting_an_expired_invite_fails_with_a_clear_message(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team] = $this->equipeComLider($event);

        $convidado = User::factory()->create(['email' => 'atrasado@example.com', 'email_verified_at' => now()]);

        $invite = TeamInvite::factory()->for($event)->for($team)->expirado()->create([
            'email' => 'atrasado@example.com',
        ]);

        $this->actingAs($convidado)
            ->get(route('team-invites.accept', $invite->token))
            ->assertRedirect(route('teams.show'))
            ->assertSessionHas('erro');

        $this->assertSame(0, TeamMember::where('user_id', $convidado->id)->count());
        $this->assertNull($invite->fresh()->accepted_at);
    }

    public function test_accepting_twice_fails(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team] = $this->equipeComLider($event);

        $convidado = User::factory()->create(['email' => 'ja-aceitou@example.com', 'email_verified_at' => now()]);

        $invite = TeamInvite::factory()->for($event)->for($team)->aceito()->create([
            'email' => 'ja-aceitou@example.com',
        ]);

        $this->actingAs($convidado)
            ->get(route('team-invites.accept', $invite->token))
            ->assertRedirect(route('teams.show'))
            ->assertSessionHas('erro');

        $this->assertSame(0, TeamMember::where('user_id', $convidado->id)->count());
    }

    public function test_accepting_with_a_different_email_fails(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team] = $this->equipeComLider($event);

        $convidado = User::factory()->create(['email' => 'certo@example.com', 'email_verified_at' => now()]);

        $invite = TeamInvite::factory()->for($event)->for($team)->create([
            'email' => 'outro@example.com',
        ]);

        $this->actingAs($convidado)
            ->get(route('team-invites.accept', $invite->token))
            ->assertRedirect(route('teams.show'))
            ->assertSessionHas('erro');

        $this->assertSame(0, TeamMember::where('user_id', $convidado->id)->count());
    }

    public function test_a_user_already_in_another_team_cannot_accept(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $leader] = $this->equipeComLider($event);

        $convidado = $this->inscrito($event);
        $outraEquipe = Team::factory()->for($event)->create(['leader_id' => $convidado->id]);
        TeamMember::factory()->for($event)->for($outraEquipe)->for($convidado)->lider()->create();

        $invite = TeamInvite::factory()->for($event)->for($team)->create([
            'email' => $convidado->email,
            'invited_by' => $leader->id,
        ]);

        $this->actingAs($convidado)
            ->get(route('team-invites.accept', $invite->token))
            ->assertRedirect(route('teams.show'))
            ->assertSessionHas('erro');

        $this->assertNull($invite->fresh()->accepted_at);
        $this->assertSame(1, TeamMember::where('user_id', $convidado->id)->count());
    }
}
