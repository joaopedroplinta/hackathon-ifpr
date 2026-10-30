<?php

namespace Tests\Feature\Participant;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/** O que a tela "Minha equipe" e o formulário de criar recebem do servidor. */
class TeamPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function inscrito(Event $event, string $nome = 'Pessoa'): User
    {
        $user = User::factory()->create(['name' => $nome, 'email_verified_at' => now()]);
        EventRegistration::factory()->for($event)->for($user)->create();

        return $user;
    }

    /** @return array{0: Team, 1: User, 2: User} */
    private function equipe(Event $event): array
    {
        $lider = $this->inscrito($event, 'Ana Líder');
        $integrante = $this->inscrito($event, 'Bruno Membro');
        $team = Team::factory()->for($event)->create(['name' => 'Os Devs', 'leader_id' => $lider->id]);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create();
        TeamMember::factory()->for($event)->for($team)->for($integrante)->create();

        return [$team, $lider, $integrante];
    }

    public function test_the_leader_sees_the_team_members_limits_and_what_they_can_do(): void
    {
        $event = Event::factory()->aberto()->create(['min_team_size' => 2, 'max_team_size' => 4]);
        [$team, $lider] = $this->equipe($event);

        $this->actingAs($lider)
            ->get(route('teams.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('equipe/minha')
                ->where('equipe.nome', 'Os Devs')
                ->where('equipe.codigo_convite', $team->invite_code)
                ->where('equipe.sou_lider', true)
                ->has('equipe.membros', 2)
                ->where('equipe.membros.0.nome', 'Ana Líder')
                ->where('equipe.membros.0.e_lider', true)
                ->where('equipe.membros.0.sou_eu', true)
                ->where('equipe.membros.1.nome', 'Bruno Membro')
                ->where('equipe.membros.1.pode_remover', true)
                ->where('limites', ['minimo' => 2, 'maximo' => 4, 'atual' => 2])
                ->where('pode_transferir', true)
            );
    }

    public function test_a_regular_member_cannot_remove_anyone_but_can_leave(): void
    {
        $event = Event::factory()->aberto()->create();
        [, , $integrante] = $this->equipe($event);

        $this->actingAs($integrante)
            ->get(route('teams.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('equipe.sou_lider', false)
                ->where('equipe.membros.0.pode_remover', false)
                ->where('equipe.membros.1.sou_eu', true)
                ->where('equipe.membros.1.pode_sair', true)
                ->where('pode_transferir', false)
            );
    }

    public function test_the_team_page_shows_the_track_and_pending_invites_with_expiry(): void
    {
        $event = Event::factory()->aberto()->create();
        [$team, $lider] = $this->equipe($event);
        $trilha = Track::factory()->for($event)->create(['name' => 'IA']);
        $team->update(['track_id' => $trilha->id]);
        TeamInvite::factory()->for($event)->for($team)->create(['email' => 'novo@ifpr.edu.br', 'invited_by' => $lider->id]);
        TeamInvite::factory()->for($event)->for($team)->expirado()->create(['email' => 'velho@ifpr.edu.br', 'invited_by' => $lider->id]);
        TeamInvite::factory()->for($event)->for($team)->aceito()->create(['email' => 'aceito@ifpr.edu.br', 'invited_by' => $lider->id]);

        $this->actingAs($lider)
            ->get(route('teams.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('equipe.trilha.name', 'IA')
                ->where('convites.pode_convidar', true)
                ->has('convites.pendentes', 2)
                ->where('convites.pendentes.0.email', fn (string $email) => in_array($email, ['novo@ifpr.edu.br', 'velho@ifpr.edu.br'], true))
                ->where('convites.pendentes', fn ($pendentes) => collect($pendentes)->contains('expirado', true) && collect($pendentes)->contains('expirado', false))
            );
    }

    public function test_a_full_team_explains_why_it_cannot_invite(): void
    {
        $event = Event::factory()->aberto()->create(['min_team_size' => 1, 'max_team_size' => 2]);
        [, $lider] = $this->equipe($event);

        $this->actingAs($lider)
            ->get(route('teams.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('convites.pode_convidar', false)
                ->where('convites.motivo_bloqueio', fn ($motivo) => is_string($motivo) && $motivo !== '')
            );
    }

    public function test_someone_without_a_team_gets_the_invitation_to_create_one(): void
    {
        $event = Event::factory()->aberto()->create();

        $this->actingAs($this->inscrito($event))
            ->get(route('teams.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('equipe/sem-equipe')
                ->where('pode_criar', true)
                ->where('inscricoes_abertas', true)
            );
    }

    public function test_the_create_form_lists_the_event_tracks_in_alphabetical_order_and_the_size_limits(): void
    {
        $event = Event::factory()->aberto()->create(['min_team_size' => 2, 'max_team_size' => 5]);
        Track::factory()->for($event)->create(['name' => 'Web']);
        Track::factory()->for($event)->create(['name' => 'IA']);

        $this->actingAs($this->inscrito($event))
            ->get(route('teams.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('equipe/criar')
                ->where('trilhas.0.name', 'IA')
                ->where('trilhas.1.name', 'Web')
                ->where('limites', ['minimo' => 2, 'maximo' => 5])
            );
    }

    public function test_someone_not_registered_cannot_open_the_create_form(): void
    {
        Event::factory()->aberto()->create();
        $semInscricao = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($semInscricao)
            ->get(route('teams.create'))
            ->assertForbidden();
    }
}
