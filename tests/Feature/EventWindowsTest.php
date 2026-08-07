<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Toda janela de tempo do evento é decidida por now() no servidor.
 * O tempo é congelado em cada teste: prazo que depende da hora real da
 * máquina falha de madrugada e passa de manhã.
 */
class EventWindowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_is_open_inside_the_window(): void
    {
        $this->travelTo('2026-09-10 12:00:00');

        $event = Event::factory()->create([
            'registration_opens_at' => '2026-09-01 00:00:00',
            'registration_closes_at' => '2026-09-20 23:59:59',
        ]);

        $this->assertTrue($event->registrationIsOpen());
    }

    public function test_registration_is_closed_before_it_opens(): void
    {
        $this->travelTo('2026-08-31 23:59:59');

        $event = Event::factory()->create([
            'registration_opens_at' => '2026-09-01 00:00:00',
            'registration_closes_at' => '2026-09-20 23:59:59',
        ]);

        $this->assertFalse($event->registrationIsOpen());
    }

    public function test_registration_is_closed_after_it_ends(): void
    {
        $this->travelTo('2026-09-21 00:00:01');

        $event = Event::factory()->create([
            'registration_opens_at' => '2026-09-01 00:00:00',
            'registration_closes_at' => '2026-09-20 23:59:59',
        ]);

        $this->assertFalse($event->registrationIsOpen());
    }

    public function test_null_bound_means_no_limit_on_that_side(): void
    {
        $this->travelTo('2030-01-01 00:00:00');

        $event = Event::factory()->create([
            'registration_opens_at' => '2026-09-01 00:00:00',
            'registration_closes_at' => null,
        ]);

        $this->assertTrue($event->registrationIsOpen());
    }

    public function test_submission_closes_exactly_at_the_deadline(): void
    {
        $event = Event::factory()->create([
            'submission_deadline' => '2026-09-20 23:59:59',
        ]);

        $this->travelTo('2026-09-20 23:59:59');
        $this->assertTrue($event->submissionIsOpen(), 'O instante do prazo ainda conta como dentro.');

        $this->travelTo('2026-09-21 00:00:00');
        $this->assertFalse($event->submissionIsOpen());
    }

    public function test_results_are_hidden_until_explicitly_published(): void
    {
        $event = Event::factory()->create();

        $this->assertFalse(
            $event->resultsArePublished(),
            'Resultado não pode aparecer sem publicação explícita do organizador.'
        );

        // Caminho explícito: atribuição direta e save(). É assim que a Action
        // PublishResults vai fazer — update() em massa não passa pelo fillable.
        $event->results_published_at = now();
        $event->save();

        $this->assertTrue($event->fresh()->resultsArePublished());
    }

    public function test_publishing_results_is_not_mass_assignable(): void
    {
        $event = Event::factory()->create();

        $event->fill(['results_published_at' => now()]);

        $this->assertNull(
            $event->results_published_at,
            'results_published_at não pode entrar por fill(): publicar é ação explícita.'
        );
    }

    public function test_draft_events_are_excluded_from_the_public_scope(): void
    {
        Event::factory()->draft()->create();
        Event::factory()->create(['status' => EventStatus::Published]);

        $this->assertSame(1, Event::query()->public()->count());
    }

    public function test_voting_is_closed_when_no_period_was_configured(): void
    {
        $this->travelTo('2026-09-10 12:00:00');

        $event = Event::factory()->create([
            'voting_opens_at' => null,
            'voting_closes_at' => null,
        ]);

        $this->assertFalse(
            $event->votingIsOpen(),
            'Votação sem período configurado tem de estar fechada, não aberta por omissão.'
        );
    }

    public function test_voting_opens_only_inside_the_configured_period(): void
    {
        $event = Event::factory()->votacaoAberta()->create();

        $this->assertTrue($event->votingIsOpen());

        $this->travelTo(now()->addDay());
        $this->assertFalse($event->votingIsOpen());
    }
}
