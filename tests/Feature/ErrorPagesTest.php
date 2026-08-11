<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Sem estas páginas, o erro do Laravel aparece cru e em inglês --
 * .claude/rules/frontend.md exige português em tudo que o usuário lê.
 * Ver bootstrap/app.php.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_missing_route_renders_the_portuguese_404_page(): void
    {
        $this->get('/esta-rota-nao-existe')
            ->assertNotFound()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('errors/erro')
                    ->where('status', 404)
            );
    }

    public function test_a_policy_denial_renders_the_portuguese_403_page(): void
    {
        $this->seed(RoleSeeder::class);
        Event::factory()->create();

        $participante = User::factory()->create(['email_verified_at' => now()]);
        $participante->assignRole(Role::Participante->value);

        $this->actingAs($participante)
            ->get(route('admin.submissions.index'))
            ->assertForbidden()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('errors/erro')
                    ->where('status', 403)
            );
    }
}
