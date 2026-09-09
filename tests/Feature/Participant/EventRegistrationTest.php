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
use PHPUnit\Framework\Attributes\DataProvider;
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
        $event = Event::factory()->aberto()->create(['collect_shirt_size' => true]);
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
        Event::factory()->aberto()->create(['collect_dietary_notes' => true]);

        $this->actingAs($this->participante())
            ->post(route('registration.store'), [
                'dietary_notes' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('dietary_notes');

        $this->assertSame(0, EventRegistration::count());
    }

    public function test_an_invalid_shirt_size_is_rejected(): void
    {
        Event::factory()->aberto()->create(['collect_shirt_size' => true]);

        $this->actingAs($this->participante())
            ->post(route('registration.store'), ['shirt_size' => 'gigante'])
            ->assertSessionHasErrors('shirt_size');
    }

    public function test_registration_form_only_receives_optional_fields_enabled_by_the_event(): void
    {
        Event::factory()->aberto()->create([
            'collect_shirt_size' => true,
            'collect_dietary_notes' => false,
        ]);

        $this->actingAs($this->participante())
            ->get(route('registration.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('coletar_camisa', true)
                ->where('coletar_restricoes_alimentares', false));
    }

    public function test_disabled_optional_fields_are_not_persisted_even_when_sent_manually(): void
    {
        Event::factory()->aberto()->create([
            'collect_shirt_size' => false,
            'collect_dietary_notes' => false,
        ]);

        $this->actingAs($this->participante())
            ->post(route('registration.store'), [
                'shirt_size' => ShirtSize::M->value,
                'dietary_notes' => 'Alergia a amendoim',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertNull(EventRegistration::firstOrFail()->shirt_size);
        $this->assertNull(EventRegistration::firstOrFail()->dietary_notes);
    }

    /** @return iterable<string, array{0: Role}> */
    public static function privilegedRoles(): iterable
    {
        yield 'jurado' => [Role::Jurado];
        yield 'organizador' => [Role::Organizador];
        yield 'admin' => [Role::Admin];
    }

    #[DataProvider('privilegedRoles')]
    public function test_a_privileged_role_cannot_register_as_a_participant(Role $role): void
    {
        Event::factory()->aberto()->create();
        $user = $this->participante();
        $user->assignRole($role->value);

        $this->actingAs($user)
            ->post(route('registration.store'), ['course' => 'ADS'])
            ->assertForbidden();

        $this->assertSame(0, EventRegistration::count());
        $this->assertFalse($user->fresh()->hasRole(Role::Participante->value));
    }
}
