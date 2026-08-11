<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\Criterion;
use App\Models\Event;
use App\Models\Rubric;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RubricTest extends TestCase
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

    public function test_staff_creates_a_rubric(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.rubrica.store'), ['name' => 'Rubrica 2026'])
            ->assertRedirect();

        $this->assertSame(1, Rubric::count());
        $this->assertFalse(Rubric::firstOrFail()->is_active);
    }

    public function test_a_participant_cannot_create_a_rubric(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->post(route('admin.rubrica.store'), ['name' => 'Rubrica 2026'])
            ->assertForbidden();

        $this->assertSame(0, Rubric::count());
    }

    public function test_staff_adds_criteria_with_decimal_weight(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.rubrica.criteria.store', $rubric), [
                'name' => 'Inovação',
                'weight' => '2.50',
                'max_score' => 10,
            ])
            ->assertRedirect();

        $criterion = Criterion::firstOrFail();
        $this->assertSame('2.50', $criterion->weight);
        $this->assertSame(10, $criterion->max_score);
    }

    public function test_a_criterion_weight_must_be_greater_than_zero(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.rubrica.criteria.store', $rubric), [
                'name' => 'Inovação',
                'weight' => '0',
                'max_score' => 10,
            ])
            ->assertSessionHasErrors('weight');

        $this->assertSame(0, Criterion::count());
    }

    public function test_staff_edits_a_criterion(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->create();
        $criterion = Criterion::factory()->for($rubric)->create(['name' => 'Nome antigo']);

        $this->actingAs($this->organizador())
            ->patch(route('admin.rubrica.criteria.update', $criterion), [
                'name' => 'Nome novo',
                'weight' => '1.00',
                'max_score' => 10,
            ])
            ->assertRedirect();

        $this->assertSame('Nome novo', $criterion->fresh()->name);
    }

    public function test_staff_removes_a_criterion(): void
    {
        $event = Event::factory()->create();
        $rubric = Rubric::factory()->for($event)->create();
        $criterion = Criterion::factory()->for($rubric)->create();

        $this->actingAs($this->organizador())
            ->delete(route('admin.rubrica.criteria.destroy', $criterion))
            ->assertRedirect();

        $this->assertSame(0, Criterion::count());
    }

    /** Só uma rubrica ativa por evento -- ativar uma desativa as outras. */
    public function test_activating_a_rubric_deactivates_the_others_in_the_same_event(): void
    {
        $event = Event::factory()->create();
        $antiga = Rubric::factory()->for($event)->ativa()->create();
        $nova = Rubric::factory()->for($event)->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.rubrica.activate', $nova))
            ->assertRedirect();

        $this->assertFalse($antiga->fresh()->is_active);
        $this->assertTrue($nova->fresh()->is_active);
    }

    /** Ativar uma rubrica de outro evento não pode desativar a ativa deste. */
    public function test_activating_a_rubric_never_touches_another_event(): void
    {
        $eventoA = Event::factory()->create(['edition' => 1]);
        $eventoB = Event::factory()->create(['edition' => 2]);
        $ativaEmA = Rubric::factory()->for($eventoA)->ativa()->create();
        $novaEmB = Rubric::factory()->for($eventoB)->create();

        $this->actingAs($this->organizador())->patch(route('admin.rubrica.activate', $novaEmB));

        $this->assertTrue($ativaEmA->fresh()->is_active);
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.rubrica.index'))->assertRedirect(route('login'));
    }

    public function test_the_public_page_shows_only_the_active_rubric(): void
    {
        $event = Event::factory()->create();
        Rubric::factory()->for($event)->create(); // inativa
        $ativa = Rubric::factory()->for($event)->ativa()->create();
        Criterion::factory()->for($ativa)->create(['name' => 'Inovação', 'weight' => '2.00']);

        $this->get(route('rubrica.show'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('publico/rubrica')
                    ->has('criterios', 1)
                    ->where('criterios.0.nome', 'Inovação')
            );
    }

    public function test_the_public_page_shows_nothing_when_no_rubric_is_active(): void
    {
        $event = Event::factory()->create();
        Rubric::factory()->for($event)->create();

        $this->get(route('rubrica.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->has('criterios', 0));
    }
}
