<?php

namespace Tests\Feature\Console;

use App\Enums\Role;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixParticipantRoleConflictsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function usuarioComConflito(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);
        $user->assignRole(Role::Jurado->value);

        return $user;
    }

    public function test_sem_conflito_nao_reporta_nada(): void
    {
        User::factory()->create()->assignRole(Role::Participante->value);

        $this->artisan('hackathon:fix-participant-role-conflicts')
            ->expectsOutputToContain('Nenhum usuário com participante + papel privilegiado.')
            ->assertExitCode(0);
    }

    public function test_dry_run_reporta_mas_nao_altera_nada(): void
    {
        $user = $this->usuarioComConflito();

        $this->artisan('hackathon:fix-participant-role-conflicts')
            ->expectsOutputToContain('Encontrados 1 usuário(s)')
            ->assertExitCode(0);

        $user->refresh();
        $this->assertTrue($user->hasRole(Role::Participante->value));
        $this->assertTrue($user->hasRole(Role::Jurado->value));
    }

    public function test_force_remove_apenas_participante(): void
    {
        $user = $this->usuarioComConflito();

        $this->artisan('hackathon:fix-participant-role-conflicts', ['--force' => true])
            ->assertExitCode(0);

        $user->refresh();
        $this->assertFalse($user->hasRole(Role::Participante->value));
        $this->assertTrue($user->hasRole(Role::Jurado->value));
    }

    public function test_e_idempotente(): void
    {
        $user = $this->usuarioComConflito();

        $this->artisan('hackathon:fix-participant-role-conflicts', ['--force' => true]);
        $this->artisan('hackathon:fix-participant-role-conflicts', ['--force' => true])
            ->expectsOutputToContain('Nenhum usuário com participante + papel privilegiado.')
            ->assertExitCode(0);

        $this->assertFalse($user->fresh()->hasRole(Role::Participante->value));
    }

    public function test_nao_toca_em_registros_historicos(): void
    {
        $event = Event::factory()->create();
        $user = $this->usuarioComConflito();
        EventRegistration::factory()->for($event)->for($user)->create();

        $this->artisan('hackathon:fix-participant-role-conflicts', ['--force' => true]);

        $this->assertDatabaseHas('event_registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }
}
