<?php

namespace Tests\Feature\Organizer;

use App\Enums\EventStatus;
use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
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

    private function dadosValidos(array $sobrescrever = []): array
    {
        return array_merge([
            'name' => 'Hackathon Atualizado',
            'description' => 'Soluções inovadoras para mobilidade urbana',
            'status' => EventStatus::Published->value,
            'registration_opens_at' => null,
            'registration_closes_at' => null,
            'starts_at' => null,
            'ends_at' => null,
            'submission_deadline' => null,
            'voting_opens_at' => null,
            'voting_closes_at' => null,
            'min_team_size' => 2,
            'max_team_size' => 5,
        ], $sobrescrever);
    }

    public function test_staff_sees_the_edit_form_with_current_data(): void
    {
        $event = Event::factory()->create(['name' => 'Meu Evento']);

        $response = $this->actingAs($this->organizador())->get(route('admin.evento.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('evento.name', $event->name));
    }

    public function test_staff_updates_the_event(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'description' => 'Soluções para acesso à saúde',
            ]))
            ->assertRedirect(route('admin.evento.edit'))
            ->assertSessionHas('sucesso');

        $event = Event::current();
        $this->assertSame('Hackathon Atualizado', $event->name);
        $this->assertSame('Soluções para acesso à saúde', $event->description);
    }

    public function test_max_team_size_cannot_be_smaller_than_the_minimum(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'min_team_size' => 5,
                'max_team_size' => 2,
            ]))
            ->assertSessionHasErrors('max_team_size');
    }

    public function test_registration_window_must_close_after_it_opens(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'registration_opens_at' => now()->addDay()->toIso8601String(),
                'registration_closes_at' => now()->toIso8601String(),
            ]))
            ->assertSessionHasErrors('registration_closes_at');
    }

    public function test_does_not_touch_fields_managed_by_their_own_action(): void
    {
        $event = Event::factory()->create();
        $event->results_published_at = now();
        $event->judges_per_submission = 4;
        $event->save();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos());

        $fresh = $event->fresh();
        $this->assertNotNull($fresh->results_published_at);
        $this->assertSame(4, $fresh->judges_per_submission);
    }

    public function test_a_participant_cannot_edit_the_event(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('admin.evento.edit'))
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->patch(route('admin.evento.update'), $this->dadosValidos())
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.evento.edit'))->assertRedirect(route('login'));
    }
}
