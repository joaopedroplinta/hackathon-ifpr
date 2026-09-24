<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckinUnknownTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /** UUID bem formado, mas de nenhum usuário -- 404, não erro de binding. */
    public function test_a_well_formed_but_unassigned_uuid_is_not_found(): void
    {
        Event::factory()->create();

        $organizador = User::factory()->create(['email_verified_at' => now()]);
        $organizador->assignRole(Role::Organizador->value);

        $this->actingAs($organizador)
            ->get('/checkin/'.Str::uuid())
            ->assertNotFound();
    }
}
