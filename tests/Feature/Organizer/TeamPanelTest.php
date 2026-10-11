<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Enums\TeamStatus;
use App\Models\Event;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPanelTest extends TestCase
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

    public function test_staff_sees_the_teams_of_the_current_edition_with_the_available_actions(): void
    {
        $event = Event::factory()->create();
        $ativa = Team::factory()->for($event)->create(['name' => 'Alfa', 'status' => TeamStatus::Confirmed]);
        $desclassificada = Team::factory()->for($event)->create(['name' => 'Beta', 'status' => TeamStatus::Disqualified]);
        Submission::factory()->for($event)->for($ativa)->enviada()->create(['title' => 'Projeto Alfa']);

        $this->actingAs($this->organizador())
            ->get(route('painel.teams.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/equipes/index')
                ->has('equipes.data', 2)
                ->where('equipes.data.0.nome', 'Alfa')
                ->where('equipes.data.0.pode_desclassificar', true)
                ->where('equipes.data.0.pode_reabilitar', false)
                ->where('equipes.data.0.submissao.titulo', 'Projeto Alfa')
                ->where('equipes.data.1.nome', 'Beta')
                ->where('equipes.data.1.pode_desclassificar', false)
                ->where('equipes.data.1.pode_reabilitar', true)
                ->where('equipes.data.1.submissao', null));

        $this->assertNotNull($desclassificada->id);
    }

    public function test_teams_from_another_edition_never_appear(): void
    {
        Event::factory()->create(['edition' => 2]);
        $antigo = Event::factory()->create(['edition' => 1]);
        Team::factory()->for($antigo)->create(['name' => 'Equipe antiga']);

        $this->actingAs($this->organizador())
            ->get(route('painel.teams.index'))
            ->assertInertia(fn ($page) => $page->has('equipes.data', 0));
    }

    public function test_the_filters_narrow_the_list(): void
    {
        $event = Event::factory()->create();
        Team::factory()->for($event)->create(['name' => 'Alfa Devs', 'status' => TeamStatus::Confirmed]);
        Team::factory()->for($event)->create(['name' => 'Beta Devs', 'status' => TeamStatus::Disqualified]);
        $organizador = $this->organizador();

        $this->actingAs($organizador)->get(route('painel.teams.index', ['busca' => 'alfa']))
            ->assertInertia(fn ($page) => $page->has('equipes.data', 1)->where('equipes.data.0.nome', 'Alfa Devs'));

        $this->actingAs($organizador)->get(route('painel.teams.index', ['status' => 'disqualified']))
            ->assertInertia(fn ($page) => $page->has('equipes.data', 1)->where('equipes.data.0.nome', 'Beta Devs'));
    }

    public function test_a_percent_sign_in_the_search_is_literal(): void
    {
        $event = Event::factory()->create();
        Team::factory()->for($event)->create(['name' => 'Equipe 100%']);
        Team::factory()->for($event)->create(['name' => 'Outra equipe']);

        $this->actingAs($this->organizador())->get(route('painel.teams.index', ['busca' => '%']))
            ->assertInertia(fn ($page) => $page->has('equipes.data', 1)->where('equipes.data.0.nome', 'Equipe 100%'));
    }

    public function test_a_participant_cannot_open_the_panel_list(): void
    {
        Event::factory()->create();
        $participante = User::factory()->create(['email_verified_at' => now()]);
        $participante->assignRole(Role::Participante->value);

        $this->actingAs($participante)->get(route('painel.teams.index'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('painel.teams.index'))->assertRedirect(route('login'));
    }
}
