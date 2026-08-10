<?php

namespace Tests\Feature\Public;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_shows_the_current_published_event(): void
    {
        Event::factory()->create(['name' => 'Hackathon de Teste', 'edition' => 3]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('publico/inicio')
                    ->where('evento.nome', 'Hackathon de Teste')
                    ->where('evento.edicao', 3)
            );
    }

    /** Evento em rascunho nunca aparece em página pública -- EventStatus::isPublic(). */
    public function test_a_draft_event_never_appears_on_the_landing(): void
    {
        Event::factory()->draft()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('evento', null));
    }

    public function test_the_landing_still_renders_with_no_event_at_all(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('evento', null));
    }

    /**
     * Sem isto o organizador esquece de abrir a janela e a landing promete
     * inscrição que o servidor recusa.
     */
    public function test_registration_open_flag_reflects_the_server_window(): void
    {
        Event::factory()->aberto()->create();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('evento.inscricoes_abertas', true));

        Event::query()->delete();
        Event::factory()->inscricoesFechadas()->create();

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('evento.inscricoes_abertas', false));
    }

    public function test_a_running_event_is_flagged_as_such(): void
    {
        Event::factory()->create(['status' => EventStatus::Running]);

        $this->get(route('home'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('evento.situacao', 'running'));
    }
}
