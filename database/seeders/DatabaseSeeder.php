<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Banco de desenvolvimento com dados realistas. Trabalhar com banco vazio
     * esconde problema -- PLANO.md secao 10.
     *
     * Idempotente de proposito: migrate:fresh esta negado em
     * .claude/settings.json, entao rodar db:seed de novo precisa funcionar
     * sem derrubar o banco.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $organizador = User::firstOrCreate(
            ['email' => 'organizacao@ifpr.edu.br'],
            [
                'name' => 'Organização do Hackathon',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );
        $organizador->syncRoles([Role::Organizador->value]);

        $event = Event::firstOrCreate(
            ['slug' => 'hackathon-ifpr-pinhais-2026'],
            Event::factory()->raw([
                'name' => '1º Hackathon IFPR Pinhais',
                'slug' => 'hackathon-ifpr-pinhais-2026',
                'edition' => 1,
            ]),
        );

        foreach (['Educação', 'Saúde', 'Cidade Inteligente'] as $name) {
            Track::firstOrCreate(
                ['event_id' => $event->id, 'name' => $name],
                ['description' => "Soluções para {$name}.", 'color' => '#2563eb'],
            );
        }

        $this->ensureUsersWithRole(Role::Participante, 12);
        $this->ensureUsersWithRole(Role::Jurado, 5);
    }

    /**
     * Completa até o total desejado em vez de criar sempre mais um lote.
     */
    private function ensureUsersWithRole(Role $role, int $total): void
    {
        $existing = User::role($role->value)->count();

        if ($existing >= $total) {
            return;
        }

        User::factory()
            ->count($total - $existing)
            ->create()
            ->each(fn (User $user) => $user->assignRole($role->value));
    }
}
