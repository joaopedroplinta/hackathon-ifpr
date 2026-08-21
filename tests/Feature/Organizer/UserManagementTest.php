<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Admin->value);

        return $user;
    }

    private function organizador(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Organizador->value);

        return $user;
    }

    public function test_admin_grants_a_role_to_another_user(): void
    {
        $alvo = User::factory()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.usuarios.update', $alvo), ['roles' => [Role::Jurado->value]])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertTrue($alvo->fresh()->hasRole(Role::Jurado->value));
    }

    public function test_admin_removes_a_role_from_another_user(): void
    {
        $alvo = User::factory()->create();
        $alvo->assignRole(Role::Jurado->value);

        $this->actingAs($this->admin())
            ->patch(route('admin.usuarios.update', $alvo), ['roles' => []])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertFalse($alvo->fresh()->hasRole(Role::Jurado->value));
    }

    public function test_role_change_is_recorded_in_the_activity_log(): void
    {
        $admin = $this->admin();
        $alvo = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.usuarios.update', $alvo), ['roles' => [Role::Organizador->value]]);

        $log = Activity::latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame($alvo->id, $log->subject_id);
        $this->assertSame([Role::Organizador->value], $log->properties['depois']);
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.usuarios.update', $admin), ['roles' => []])
            ->assertSessionHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole(Role::Admin->value));
    }

    public function test_an_organizer_cannot_manage_roles(): void
    {
        $alvo = User::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.usuarios.update', $alvo), ['roles' => [Role::Jurado->value]])
            ->assertForbidden();

        $this->assertFalse($alvo->fresh()->hasRole(Role::Jurado->value));
    }

    public function test_an_organizer_cannot_view_the_user_list(): void
    {
        $this->actingAs($this->organizador())
            ->get(route('admin.usuarios.index'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.usuarios.index'))->assertRedirect(route('login'));
    }

    public function test_roles_must_be_valid_values(): void
    {
        $alvo = User::factory()->create();

        $this->actingAs($this->admin())
            ->patch(route('admin.usuarios.update', $alvo), ['roles' => ['superusuario']])
            ->assertSessionHasErrors('roles.0');
    }
}
