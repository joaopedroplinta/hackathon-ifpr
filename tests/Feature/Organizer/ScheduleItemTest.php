<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\Event;
use App\Models\ScheduleItem;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ScheduleItemTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    private function itemValido(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Abertura e boas-vindas',
            'type' => 'palestra',
            'starts_at' => now()->addDay()->toIso8601String(),
            'ends_at' => now()->addDay()->addHour()->toIso8601String(),
        ], $overrides);
    }

    public function test_staff_sees_every_item_including_drafts(): void
    {
        $event = Event::factory()->create();
        ScheduleItem::factory()->for($event)->create(['title' => 'Rascunho']);
        ScheduleItem::factory()->for($event)->publicado()->create(['title' => 'Publicado']);

        $this->actingAs($this->organizador())
            ->get(route('painel.agenda.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('admin/agenda/index')->has('itens', 2));
    }

    public function test_a_participant_cannot_open_the_admin_agenda(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('painel.agenda.index'))
            ->assertForbidden();
    }

    public function test_staff_creates_an_item_as_a_draft(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('painel.agenda.store'), $this->itemValido())
            ->assertRedirect(route('painel.agenda.index'))
            ->assertSessionHas('sucesso');

        $item = ScheduleItem::firstOrFail();
        $this->assertSame('Abertura e boas-vindas', $item->title);
        $this->assertFalse($item->is_published);
    }

    public function test_a_participant_cannot_create_an_item(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->post(route('painel.agenda.store'), $this->itemValido())
            ->assertForbidden();

        $this->assertSame(0, ScheduleItem::count());
    }

    public function test_the_end_time_must_come_after_the_start_time(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('painel.agenda.store'), $this->itemValido([
                'starts_at' => now()->addDay()->toIso8601String(),
                'ends_at' => now()->addDay()->subHour()->toIso8601String(),
            ]))
            ->assertSessionHasErrors('ends_at');

        $this->assertSame(0, ScheduleItem::count());
    }

    /** Trilha só pode ser do próprio evento -- mesma regra do filtro de submissões. */
    public function test_a_track_from_another_event_is_rejected(): void
    {
        Event::factory()->create(['edition' => 2]);
        $outro = Event::factory()->create(['edition' => 1]);
        $trilhaAlheia = Track::factory()->for($outro)->create();

        $this->actingAs($this->organizador())
            ->post(route('painel.agenda.store'), $this->itemValido(['track_id' => $trilhaAlheia->id]))
            ->assertSessionHasErrors('track_id');
    }

    public function test_staff_updates_an_item(): void
    {
        $event = Event::factory()->create();
        $item = ScheduleItem::factory()->for($event)->create(['title' => 'Título antigo']);

        $this->actingAs($this->organizador())
            ->patch(route('painel.agenda.update', $item), $this->itemValido(['title' => 'Título novo']))
            ->assertRedirect(route('painel.agenda.index'));

        $this->assertSame('Título novo', $item->fresh()->title);
    }

    public function test_publishing_makes_the_item_appear_on_the_public_agenda_immediately(): void
    {
        $event = Event::factory()->create();
        $item = ScheduleItem::factory()->for($event)->create();

        $this->get(route('agenda.index'))->assertInertia(fn (AssertableInertia $page) => $page->has('itens', 0));

        $this->actingAs($this->organizador())->patch(route('painel.agenda.publish', $item));

        $this->assertTrue($item->fresh()->is_published);
        $this->get(route('agenda.index'))->assertInertia(fn (AssertableInertia $page) => $page->has('itens', 1));
    }

    public function test_unpublishing_removes_the_item_from_the_public_agenda_immediately(): void
    {
        $event = Event::factory()->create();
        $item = ScheduleItem::factory()->for($event)->publicado()->create();

        $this->actingAs($this->organizador())->patch(route('painel.agenda.publish', $item));

        $this->assertFalse($item->fresh()->is_published);
        $this->get(route('agenda.index'))->assertInertia(fn (AssertableInertia $page) => $page->has('itens', 0));
    }

    public function test_a_participant_cannot_toggle_publication(): void
    {
        $event = Event::factory()->create();
        $item = ScheduleItem::factory()->for($event)->create();

        $this->actingAs($this->participante())
            ->patch(route('painel.agenda.publish', $item))
            ->assertForbidden();

        $this->assertFalse($item->fresh()->is_published);
    }

    public function test_staff_deletes_an_item(): void
    {
        $event = Event::factory()->create();
        $item = ScheduleItem::factory()->for($event)->create();

        $this->actingAs($this->organizador())
            ->delete(route('painel.agenda.destroy', $item))
            ->assertRedirect(route('painel.agenda.index'));

        $this->assertSame(0, ScheduleItem::count());
    }

    public function test_a_participant_cannot_delete_an_item(): void
    {
        $event = Event::factory()->create();
        $item = ScheduleItem::factory()->for($event)->create();

        $this->actingAs($this->participante())
            ->delete(route('painel.agenda.destroy', $item))
            ->assertForbidden();

        $this->assertSame(1, ScheduleItem::count());
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('painel.agenda.index'))->assertRedirect(route('login'));
    }
}
