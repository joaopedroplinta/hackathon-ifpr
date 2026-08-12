<?php

namespace Tests\Feature\Organizer;

use App\Enums\CertificateType;
use App\Enums\Role;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CertificateTest extends TestCase
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

    public function test_staff_issues_a_one_off_certificate(): void
    {
        Queue::fake();

        $event = Event::factory()->create();
        $mentor = $this->participante();

        $this->actingAs($this->organizador())
            ->post(route('admin.certificados.store'), [
                'user_id' => $mentor->id,
                'type' => CertificateType::Mentor->value,
            ])
            ->assertRedirect(route('admin.certificados.index'))
            ->assertSessionHas('sucesso');

        $this->assertDatabaseHas('certificates', [
            'event_id' => $event->id,
            'user_id' => $mentor->id,
            'type' => CertificateType::Mentor->value,
        ]);
    }

    public function test_an_invalid_type_is_rejected(): void
    {
        Event::factory()->create();
        $pessoa = $this->participante();

        $this->actingAs($this->organizador())
            ->post(route('admin.certificados.store'), [
                'user_id' => $pessoa->id,
                'type' => 'inexistente',
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(0, Certificate::count());
    }

    public function test_a_participant_cannot_issue_certificates(): void
    {
        Event::factory()->create();
        $pessoa = $this->participante();

        $this->actingAs($this->participante())
            ->post(route('admin.certificados.store'), [
                'user_id' => $pessoa->id,
                'type' => CertificateType::Participacao->value,
            ])
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->get(route('admin.certificados.index'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.certificados.index'))->assertRedirect(route('login'));
    }
}
