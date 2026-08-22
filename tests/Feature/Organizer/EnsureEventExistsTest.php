<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cobre a middleware de forma transversal, sem repetir em cada teste de
 * cada controller do organizador -- ver App\Http\Middleware\EnsureEventExists.
 */
class EnsureEventExistsTest extends TestCase
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

    private function participante(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    public function test_staff_sees_the_no_event_screen_instead_of_a_raw_404(): void
    {
        $this->actingAs($this->organizador())
            ->get(route('admin.agenda.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/sem-evento'));
    }

    public function test_the_screen_no_longer_appears_once_an_event_exists(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.agenda.index'))
            ->assertInertia(fn ($page) => $page->component('admin/agenda/index'));
    }

    /**
     * Quem nem deveria acessar a rota continua vendo 403 da Policy, não a
     * tela de "crie o evento" -- a middleware não pode vazar estado do
     * sistema pra quem não tem permissão nenhuma ali.
     */
    public function test_a_participant_without_access_still_gets_forbidden_not_the_no_event_screen(): void
    {
        $this->actingAs($this->participante())
            ->get(route('admin.agenda.index'))
            ->assertForbidden();
    }

    /** Gestão de papel de usuário não depende de ter um evento cadastrado. */
    public function test_user_management_stays_reachable_without_any_event(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/usuarios/index'));
    }
}
