<?php

namespace Tests\Feature\Console;

use App\Enums\CertificateType;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\Checkpoint;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IssueCertificatesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_issues_certificates_for_the_event_resolved_by_slug(): void
    {
        Queue::fake();

        $event = Event::factory()->create();
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);
        EventRegistration::factory()->for($event)->for($user)->create();
        Attendance::factory()
            ->for(Checkpoint::factory()->for($event)->create())
            ->for($user)
            ->create();

        $this->artisan('hackathon:issue-certificates', ['event' => $event->slug])
            ->assertExitCode(0);

        $this->assertDatabaseHas('certificates', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'type' => CertificateType::Participacao->value,
        ]);
    }

    public function test_fails_gracefully_for_an_unknown_slug(): void
    {
        $this->artisan('hackathon:issue-certificates', ['event' => 'nao-existe'])
            ->assertExitCode(1);
    }
}
