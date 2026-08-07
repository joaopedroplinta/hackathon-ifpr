<?php

namespace Tests\Feature\Participant;

use App\Enums\Role;
use App\Enums\ShirtSize;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function participante(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_a_verified_user_can_register(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->participante();

        $response = $this->actingAs($user)->post(route('registration.store'), [
            'shirt_size' => ShirtSize::M->value,
            'course' => 'ADS',
            'phone' => '(41) 90000-0000',
        ]);

        $response->assertRedirect(route('dashboard'));

        $registration = EventRegistration::firstOrFail();

        $this->assertSame($event->id, $registration->event_id);
        $this->assertSame($user->id, $registration->user_id);
        $this->assertNotNull($registration->registered_at);
        $this->assertSame(ShirtSize::M, $registration->shirt_size);
        $this->assertTrue($user->fresh()->hasRole(Role::Participante->value));
    }

    public function test_registration_is_refused_after_it_closes(): void
    {
        Event::factory()->inscricoesFechadas()->create();
        $user = $this->participante();

        $this->actingAs($user)
            ->post(route('registration.store'), ['course' => 'ADS'])
            ->assertForbidden();

        $this->assertSame(0, EventRegistration::count(), 'Nada pode ser gravado quando o prazo passou.');
    }

    public function test_registration_is_refused_before_it_opens(): void
    {
        Event::factory()->inscricoesNaoAbertas()->create();

        $this->actingAs($this->participante())
            ->post(route('registration.store'), ['course' => 'ADS'])
            ->assertForbidden();

        $this->assertSame(0, EventRegistration::count());
    }

    public function test_the_form_is_not_reachable_when_registration_is_closed(): void
    {
        Event::factory()->inscricoesFechadas()->create();

        $this->actingAs($this->participante())
            ->get(route('registration.create'))
            ->assertForbidden();
    }

    public function test_a_user_cannot_register_twice(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->participante();

        EventRegistration::factory()->for($event)->for($user)->create();

        $this->actingAs($user)
            ->post(route('registration.store'), ['course' => 'ADS'])
            ->assertForbidden();

        $this->assertSame(1, EventRegistration::count());
    }

    public function test_the_database_refuses_a_duplicate_even_without_the_policy(): void
    {
        $event = Event::factory()->aberto()->create();
        $user = $this->participante();

        EventRegistration::factory()->for($event)->for($user)->create();

        // Simula a corrida do duplo clique, que passaria por cima da checagem
        // da aplicação. O unique composto no banco é a única garantia real.
        $this->expectException(UniqueConstraintViolationException::class);

        EventRegistration::factory()->for($event)->for($user)->create();
    }

    public function test_an_unverified_user_is_sent_to_the_verification_notice(): void
    {
        Event::factory()->aberto()->create();
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user)
            ->get(route('registration.create'))
            ->assertRedirect(route('verification.notice'));

        $this->assertSame(0, EventRegistration::count());
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        Event::factory()->aberto()->create();

        $this->get(route('registration.create'))->assertRedirect(route('login'));
    }

    public function test_registering_with_no_published_event_returns_not_found(): void
    {
        Event::factory()->draft()->create();

        $this->actingAs($this->participante())
            ->get(route('registration.create'))
            ->assertNotFound();
    }

    public function test_dietary_notes_longer_than_the_limit_are_rejected(): void
    {
        Event::factory()->aberto()->create();

        $this->actingAs($this->participante())
            ->post(route('registration.store'), [
                'dietary_notes' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('dietary_notes');

        $this->assertSame(0, EventRegistration::count());
    }

    public function test_an_invalid_shirt_size_is_rejected(): void
    {
        Event::factory()->aberto()->create();

        $this->actingAs($this->participante())
            ->post(route('registration.store'), ['shirt_size' => 'gigante'])
            ->assertSessionHasErrors('shirt_size');
    }
}
