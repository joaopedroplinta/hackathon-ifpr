<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Enums\ScheduleItemType;
use App\Models\Event;
use App\Models\ScheduleItem;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/** Formulários de criar e editar item da agenda: o que a tela recebe. */
class ScheduleItemFormTest extends TestCase
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

    public function test_the_create_form_offers_every_type_and_the_event_tracks_by_name(): void
    {
        $event = Event::factory()->create(['edition' => 5]);
        Track::factory()->for($event)->create(['name' => 'Web']);
        Track::factory()->for($event)->create(['name' => 'IA']);
        Track::factory()->for(Event::factory()->create(['edition' => 1]))->create(['name' => 'De outra edição']);

        $this->actingAs($this->organizador())
            ->get(route('painel.agenda.create'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/agenda/formulario')
                ->where('item', null)
                ->has('opcoes.tipos', count(ScheduleItemType::cases()))
                ->has('opcoes.trilhas', 2)
                ->where('opcoes.trilhas.0.nome', 'IA')
                ->where('opcoes.trilhas.1.nome', 'Web')
            );
    }

    public function test_the_edit_form_carries_the_saved_item_with_iso_dates(): void
    {
        $event = Event::factory()->create();
        $trilha = Track::factory()->for($event)->create();
        $item = ScheduleItem::factory()->for($event)->create([
            'title' => 'Abertura',
            'type' => ScheduleItemType::Palestra,
            'starts_at' => '2026-05-10 12:00:00',
            'ends_at' => '2026-05-10 13:30:00',
            'location' => 'Auditório',
            'speaker_name' => 'Dra. Silva',
            'track_id' => $trilha->id,
        ]);

        $this->actingAs($this->organizador())
            ->get(route('painel.agenda.edit', $item))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('item.id', $item->id)
                ->where('item.title', 'Abertura')
                ->where('item.type', 'palestra')
                ->where('item.starts_at', fn (string $iso) => str_starts_with($iso, '2026-05-10T12:00:00'))
                ->where('item.location', 'Auditório')
                ->where('item.speaker_name', 'Dra. Silva')
                ->where('item.track_id', $trilha->id)
            );
    }

    public function test_an_item_from_another_edition_cannot_be_edited(): void
    {
        Event::factory()->create(['edition' => 5]);
        $antigo = ScheduleItem::factory()->for(Event::factory()->create(['edition' => 1]))->create();

        $this->actingAs($this->organizador())
            ->get(route('painel.agenda.edit', $antigo))
            ->assertNotFound();
    }

    public function test_a_participant_cannot_open_the_agenda_forms(): void
    {
        $event = Event::factory()->create();
        $item = ScheduleItem::factory()->for($event)->create();
        $participante = $this->participante();

        $this->actingAs($participante)->get(route('painel.agenda.create'))->assertForbidden();
        $this->actingAs($participante)->get(route('painel.agenda.edit', $item))->assertForbidden();
    }
}
