<?php

namespace Tests\Feature\Organizer;

use App\Enums\IncidentKind;
use App\Enums\Role;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentTest extends TestCase
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

    public function test_staff_declares_an_incident_without_extension(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('painel.incidentes.store'), [
                'kind' => IncidentKind::Rede->value,
                'description' => 'Wi-fi do campus caiu por 5 minutos.',
                'deadline_extension_minutes' => 0,
            ])
            ->assertRedirect(route('painel.incidentes.index'))
            ->assertSessionHas('sucesso');

        $this->assertDatabaseHas('incidents', [
            'event_id' => $event->id,
            'kind' => IncidentKind::Rede->value,
            'deadline_extension_minutes' => 0,
        ]);
        $this->assertSame($event->submission_deadline->toIso8601String(), $event->fresh()->effectiveSubmissionDeadline()->toIso8601String());
    }

    public function test_extension_applies_to_every_team_not_just_one(): void
    {
        $event = Event::factory()->create(['submission_deadline' => now()->subMinute()]);

        $team = Team::factory()->for($event)->create();
        $lider = User::factory()->create(['email_verified_at' => now()]);
        TeamMember::factory()->for($event)->for($team)->for($lider)->lider()->create(['status' => TeamMemberStatus::Active]);

        $this->assertFalse($event->submissionIsOpen());

        $this->actingAs($this->organizador())->post(route('painel.incidentes.store'), [
            'kind' => IncidentKind::Sistema->value,
            'description' => 'Servidor ficou fora do ar por 10 minutos.',
            'deadline_extension_minutes' => 15,
        ]);

        $event->refresh();
        $this->assertTrue($event->submissionIsOpen());
        $this->assertSame(
            $event->submission_deadline->clone()->addMinutes(15)->toIso8601String(),
            $event->effectiveSubmissionDeadline()->toIso8601String(),
        );
    }

    public function test_multiple_incidents_accumulate_the_extension(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->organizador())->post(route('painel.incidentes.store'), [
            'kind' => IncidentKind::Rede->value,
            'description' => 'Primeira queda de rede do dia.',
            'deadline_extension_minutes' => 10,
        ]);
        $this->actingAs($this->organizador())->post(route('painel.incidentes.store'), [
            'kind' => IncidentKind::Energia->value,
            'description' => 'Falta de energia breve no bloco B.',
            'deadline_extension_minutes' => 20,
        ]);

        $event->refresh();
        $this->assertSame(
            $event->submission_deadline->clone()->addMinutes(30)->toIso8601String(),
            $event->effectiveSubmissionDeadline()->toIso8601String(),
        );
    }

    public function test_a_short_description_is_rejected(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('painel.incidentes.store'), [
                'kind' => IncidentKind::Outro->value,
                'description' => 'curto',
                'deadline_extension_minutes' => 0,
            ])
            ->assertSessionHasErrors('description');
    }

    public function test_a_participant_cannot_declare_incidents(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('painel.incidentes.index'))
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->post(route('painel.incidentes.store'), [
                'kind' => IncidentKind::Outro->value,
                'description' => 'Tentativa sem permissão de declarar.',
                'deadline_extension_minutes' => 0,
            ])
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('painel.incidentes.index'))->assertRedirect(route('login'));
    }
}
