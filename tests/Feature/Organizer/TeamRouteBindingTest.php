<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Enums\TeamStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Achado na homologação: a tela chama as rotas pelo id numérico, mas Team
 * resolve rota por slug -- os outros testes montam a URL a partir do model
 * (que usa o slug) e por isso não viam o 404.
 */
class TeamRouteBindingTest extends TestCase
{
    use RefreshDatabase;

    private const MOTIVO = 'Projeto copiado de repositório público sem declarar a origem.';

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

    public function test_the_actions_work_with_the_numeric_id_the_screen_sends(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create(['status' => TeamStatus::Confirmed]);
        $organizador = $this->organizador();

        $this->actingAs($organizador)
            ->post("/painel/equipes/{$team->id}/desclassificar", ['motivo' => self::MOTIVO])
            ->assertSessionHas('sucesso');
        $this->assertSame(TeamStatus::Disqualified, $team->fresh()->status);

        $this->actingAs($organizador)
            ->post("/painel/equipes/{$team->id}/reabilitar", ['motivo' => 'Decisão revista pela comissão.'])
            ->assertSessionHas('sucesso');
        $this->assertSame(TeamStatus::Confirmed, $team->fresh()->status);
    }

    public function test_a_slug_shared_with_a_past_edition_never_targets_the_wrong_team(): void
    {
        $antigo = Event::factory()->create(['edition' => 1]);
        $atual = Event::factory()->create(['edition' => 2]);
        $antiga = Team::factory()->for($antigo)->create(['slug' => 'mesmo-slug', 'status' => TeamStatus::Confirmed]);
        $nova = Team::factory()->for($atual)->create(['slug' => 'mesmo-slug', 'status' => TeamStatus::Confirmed]);

        $this->actingAs($this->organizador())
            ->post("/painel/equipes/{$nova->id}/desclassificar", ['motivo' => self::MOTIVO])
            ->assertSessionHas('sucesso');

        $this->assertSame(TeamStatus::Disqualified, $nova->fresh()->status);
        $this->assertSame(TeamStatus::Confirmed, $antiga->fresh()->status);
    }
}
