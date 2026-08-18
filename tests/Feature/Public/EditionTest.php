<?php

namespace Tests\Feature\Public;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_finished_editions_with_published_results(): void
    {
        $edicao = Event::factory()->create([
            'edition' => 1,
            'status' => EventStatus::Finished,
            'results_published_at' => now(),
        ]);

        $response = $this->get(route('edicoes.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('edicoes', 1)
            ->where('edicoes.0.nome', $edicao->name)
            ->where('edicoes.0.slug', $edicao->slug)
        );
    }

    public function test_excludes_finished_editions_without_published_results(): void
    {
        Event::factory()->create([
            'status' => EventStatus::Finished,
            'results_published_at' => null,
        ]);

        $response = $this->get(route('edicoes.index'));

        $response->assertInertia(fn ($page) => $page->where('edicoes', []));
    }

    public function test_excludes_the_current_running_edition_even_with_a_timestamp(): void
    {
        Event::factory()->create([
            'status' => EventStatus::Published,
            'results_published_at' => null,
        ]);

        $response = $this->get(route('edicoes.index'));

        $response->assertInertia(fn ($page) => $page->where('edicoes', []));
    }

    public function test_excludes_draft_editions(): void
    {
        Event::factory()->draft()->create([
            'results_published_at' => now(),
        ]);

        $response = $this->get(route('edicoes.index'));

        $response->assertInertia(fn ($page) => $page->where('edicoes', []));
    }

    public function test_orders_by_edition_descending(): void
    {
        $antiga = Event::factory()->create([
            'edition' => 1,
            'status' => EventStatus::Finished,
            'results_published_at' => now(),
        ]);
        $recente = Event::factory()->create([
            'edition' => 2,
            'status' => EventStatus::Finished,
            'results_published_at' => now(),
        ]);

        $response = $this->get(route('edicoes.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('edicoes.0.slug', $recente->slug)
            ->where('edicoes.1.slug', $antiga->slug)
        );
    }
}
