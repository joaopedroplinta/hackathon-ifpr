<?php

namespace Tests\Feature\Public;

use App\Models\Event;
use App\Models\ScheduleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_items_appear_on_the_public_agenda(): void
    {
        $event = Event::factory()->create();
        ScheduleItem::factory()->for($event)->publicado()->create(['title' => 'Abertura do evento']);

        $this->get(route('agenda.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('publico/agenda')
                    ->has('itens', 1)
                    ->where('itens.0.titulo', 'Abertura do evento')
            );
    }

    public function test_a_draft_item_never_appears(): void
    {
        $event = Event::factory()->create();
        ScheduleItem::factory()->for($event)->create(['title' => 'Ainda não pronto']);

        $this->get(route('agenda.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('itens', 0));
    }

    public function test_an_item_from_another_event_never_appears(): void
    {
        Event::factory()->create(['edition' => 2]);
        $anterior = Event::factory()->create(['edition' => 1]);
        ScheduleItem::factory()->for($anterior)->publicado()->create();

        $this->get(route('agenda.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('itens', 0));
    }

    public function test_the_agenda_still_renders_with_no_event_at_all(): void
    {
        $this->get(route('agenda.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('evento', null)->has('itens', 0));
    }

    public function test_the_ics_download_contains_only_published_items(): void
    {
        $event = Event::factory()->create();
        ScheduleItem::factory()->for($event)->publicado()->create(['title' => 'Palestra de abertura']);
        ScheduleItem::factory()->for($event)->create(['title' => 'Rascunho, não deveria aparecer']);

        $response = $this->get(route('agenda.ics'))->assertOk();

        $response->assertHeader('content-type', 'text/calendar; charset=utf-8');

        $conteudo = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $conteudo);
        $this->assertStringContainsString('SUMMARY:Palestra de abertura', $conteudo);
        $this->assertStringNotContainsString('Rascunho', $conteudo);
    }

    /** Vírgula no título não pode quebrar o campo seguinte do .ics -- RFC 5545. */
    public function test_the_ics_escapes_special_characters(): void
    {
        $event = Event::factory()->create();
        ScheduleItem::factory()->for($event)->publicado()->create([
            'title' => 'Workshop: React, Laravel e Inertia',
        ]);

        $conteudo = $this->get(route('agenda.ics'))->getContent();

        $this->assertStringContainsString('SUMMARY:Workshop: React\\, Laravel e Inertia', $conteudo);
    }
}
