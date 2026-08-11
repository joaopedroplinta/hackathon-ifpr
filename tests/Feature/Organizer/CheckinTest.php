<?php

namespace Tests\Feature\Organizer;

use App\Enums\AttendanceMethod;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\Checkpoint;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CheckinTest extends TestCase
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

    public function test_staff_confirms_attendance_through_the_qr_token(): void
    {
        $event = Event::factory()->create();
        $checkpoint = Checkpoint::factory()->for($event)->create(['name' => 'Entrada']);
        $participante = $this->participante();
        $staff = $this->organizador();

        $this->actingAs($staff)
            ->get(route('checkin.show', $participante->qr_token))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('admin/checkin/confirmar')
                    ->where('participante.nome', $participante->name)
                    ->where('ja_confirmado', false)
            );

        $this->actingAs($staff)
            ->post(route('checkin.store', $participante->qr_token), ['checkpoint_id' => $checkpoint->id])
            ->assertRedirect();

        $this->assertSame(1, Attendance::count());
        $attendance = Attendance::firstOrFail();
        $this->assertSame($participante->id, $attendance->user_id);
        $this->assertSame($checkpoint->id, $attendance->checkpoint_id);
        $this->assertSame($staff->id, $attendance->checked_by);
        $this->assertSame(AttendanceMethod::Qr, $attendance->method);
    }

    /** Controle é sempre da organização -- PLANO.md, seção 4. */
    public function test_a_participant_cannot_confirm_their_own_attendance(): void
    {
        $event = Event::factory()->create();
        $checkpoint = Checkpoint::factory()->for($event)->create();
        $participante = $this->participante();

        $this->actingAs($participante)
            ->get(route('checkin.show', $participante->qr_token))
            ->assertForbidden();

        $this->actingAs($participante)
            ->post(route('checkin.store', $participante->qr_token), ['checkpoint_id' => $checkpoint->id])
            ->assertForbidden();

        $this->assertSame(0, Attendance::count());
    }

    /** Token inválido é 404 tratado, não estouro de exceção. */
    public function test_an_invalid_token_is_a_clean_not_found(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->get('/checkin/token-que-nao-existe')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('errors/erro')->where('status', 404));
    }

    public function test_confirming_the_same_checkpoint_twice_does_not_duplicate(): void
    {
        $event = Event::factory()->create();
        $checkpoint = Checkpoint::factory()->for($event)->create();
        $participante = $this->participante();
        $staff = $this->organizador();

        $this->actingAs($staff)->post(route('checkin.store', $participante->qr_token), ['checkpoint_id' => $checkpoint->id]);
        $this->actingAs($staff)->post(route('checkin.store', $participante->qr_token), ['checkpoint_id' => $checkpoint->id]);

        $this->assertSame(1, Attendance::count());
    }

    public function test_the_confirmation_screen_reflects_an_existing_attendance(): void
    {
        $event = Event::factory()->create();
        $checkpoint = Checkpoint::factory()->for($event)->create();
        $participante = $this->participante();
        $staff = $this->organizador();

        Attendance::factory()->for($checkpoint)->for($participante)->create(['checked_by' => $staff->id]);

        $this->actingAs($staff)
            ->get(route('checkin.show', ['user' => $participante->qr_token, 'checkpoint' => $checkpoint->id]))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('ja_confirmado', true));
    }

    public function test_manual_search_finds_a_registered_participant_by_name(): void
    {
        $event = Event::factory()->create();
        $participante = User::factory()->create(['name' => 'Beatriz Nogueira']);
        EventRegistration::factory()->for($event)->for($participante)->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.checkin.index', ['busca' => 'beatriz']))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('resultados', 1)
                    ->where('resultados.0.nome', 'Beatriz Nogueira')
                    ->where('resultados.0.confirmar_href', fn ($href) => str_contains($href, 'via=busca'))
            );
    }

    /** A busca nunca inclui o qr_token cru na resposta. */
    public function test_manual_search_never_exposes_the_raw_qr_token(): void
    {
        $event = Event::factory()->create();
        $participante = User::factory()->create(['name' => 'Carlos Aguiar']);
        EventRegistration::factory()->for($event)->for($participante)->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.checkin.index', ['busca' => 'carlos']))
            ->assertInertia(fn (AssertableInertia $page) => $page->missing('resultados.0.qr_token'));
    }

    /** Confirmar via busca marca method=manual, sem passar perto de um QR. */
    public function test_confirming_through_manual_search_is_recorded_as_manual(): void
    {
        $event = Event::factory()->create();
        $checkpoint = Checkpoint::factory()->for($event)->create();
        $participante = $this->participante();

        $this->actingAs($this->organizador())
            ->post(route('checkin.store', ['user' => $participante->qr_token, 'via' => 'busca']), [
                'checkpoint_id' => $checkpoint->id,
            ]);

        $this->assertSame(AttendanceMethod::Manual, Attendance::firstOrFail()->method);
    }

    public function test_someone_not_registered_for_the_event_never_appears_in_the_search(): void
    {
        // Edition maior é a que Event::current() resolve.
        Event::factory()->create(['edition' => 2]);
        $outroEvento = Event::factory()->create(['edition' => 1]);
        $foraDoEvento = User::factory()->create(['name' => 'Diego Fora']);
        EventRegistration::factory()->for($outroEvento)->for($foraDoEvento)->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.checkin.index', ['busca' => 'diego']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('resultados', 0));
    }

    public function test_staff_creates_the_first_checkpoint(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.checkin.checkpoints.store'), ['name' => 'Entrada', 'type' => 'entrada'])
            ->assertRedirect(route('admin.checkin.index'));

        $this->assertSame(1, Checkpoint::count());
    }

    public function test_a_participant_cannot_create_a_checkpoint(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->post(route('admin.checkin.checkpoints.store'), ['name' => 'Entrada', 'type' => 'entrada'])
            ->assertForbidden();

        $this->assertSame(0, Checkpoint::count());
    }

    public function test_the_confirmation_screen_explains_when_no_checkpoint_exists_yet(): void
    {
        Event::factory()->create();
        $participante = $this->participante();

        $this->actingAs($this->organizador())
            ->get(route('checkin.show', $participante->qr_token))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('checkpoints', 0)->where('checkpoint_selecionado_id', null));
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();
        $participante = $this->participante();

        $this->get(route('checkin.show', $participante->qr_token))->assertRedirect(route('login'));
        $this->get(route('admin.checkin.index'))->assertRedirect(route('login'));
    }
}
