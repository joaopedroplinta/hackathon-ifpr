<?php

namespace Tests\Feature\Organizer;

use App\Enums\EventStatus;
use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // O disco 'local' do Laravel 12 é storage/app/private.
        Storage::fake('local');
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

    private function dadosValidos(array $sobrescrever = []): array
    {
        return array_merge([
            'name' => 'Hackathon Atualizado',
            'description' => 'Soluções inovadoras para mobilidade urbana',
            'status' => EventStatus::Published->value,
            'registration_opens_at' => null,
            'registration_closes_at' => null,
            'starts_at' => null,
            'ends_at' => null,
            'submission_deadline' => null,
            'voting_opens_at' => null,
            'voting_closes_at' => null,
            'min_team_size' => 2,
            'max_team_size' => 5,
        ], $sobrescrever);
    }

    public function test_edit_shows_the_no_event_screen_when_no_event_exists(): void
    {
        $this->actingAs($this->organizador())
            ->get(route('admin.evento.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/sem-evento'));
    }

    public function test_staff_sees_the_create_form_when_no_event_exists(): void
    {
        $this->actingAs($this->organizador())
            ->get(route('admin.evento.create'))
            ->assertOk();
    }

    public function test_create_redirects_to_edit_when_an_event_already_exists(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.evento.create'))
            ->assertRedirect(route('admin.evento.edit'));
    }

    /**
     * Sempre Publicado -- Event::current() (usado por toda tela do
     * organizador via currentEventOrFail) filtra fora quem está em
     * Rascunho. Criar em Rascunho autobloquearia o organizador de
     * configurar qualquer coisa até publicar.
     */
    public function test_staff_creates_the_first_event_already_published(): void
    {
        $this->actingAs($this->organizador())
            ->post(route('admin.evento.store'), [
                'name' => 'Hackathon IFPR 2026',
                'description' => 'Soluções para mobilidade urbana',
                'min_team_size' => 2,
                'max_team_size' => 5,
            ])
            ->assertRedirect(route('admin.evento.edit'))
            ->assertSessionHas('sucesso');

        $event = Event::current();
        $this->assertNotNull($event);
        $this->assertSame('Hackathon IFPR 2026', $event->name);
        $this->assertSame(EventStatus::Published, $event->status);
        $this->assertSame(1, $event->edition);
    }

    /** Anexar o edital já na criação poupa uma segunda ida a Editar evento. */
    public function test_staff_attaches_the_regulation_while_creating_the_event(): void
    {
        $this->actingAs($this->organizador())
            ->post(route('admin.evento.store'), [
                'name' => 'Hackathon IFPR 2026',
                'min_team_size' => 1,
                'max_team_size' => 4,
                'regulamento' => UploadedFile::fake()->create('edital-2026.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.evento.edit'))
            ->assertSessionHas('sucesso');

        $event = Event::current();
        $this->assertSame('edital-2026.pdf', $event->regulation_original_name);
        $this->assertNotNull($event->regulation_updated_at);
        Storage::disk('local')->assertExists($event->regulation_path);
    }

    public function test_creating_the_event_without_a_regulation_still_works(): void
    {
        $this->actingAs($this->organizador())
            ->post(route('admin.evento.store'), [
                'name' => 'Hackathon IFPR 2026',
                'min_team_size' => 1,
                'max_team_size' => 4,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertNull(Event::current()->regulation_path);
    }

    public function test_a_second_event_gets_the_next_edition_number(): void
    {
        Event::factory()->create(['edition' => 1]);

        $this->actingAs($this->organizador())->post(route('admin.evento.store'), [
            'name' => 'Hackathon IFPR 2027',
            'min_team_size' => 1,
            'max_team_size' => 4,
        ]);

        $this->assertSame(2, Event::current()->edition);
    }

    public function test_a_participant_cannot_create_the_event(): void
    {
        $this->actingAs($this->participante())
            ->get(route('admin.evento.create'))
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->post(route('admin.evento.store'), ['name' => 'Tentativa', 'min_team_size' => 1, 'max_team_size' => 4])
            ->assertForbidden();
    }

    public function test_staff_sees_the_edit_form_with_current_data(): void
    {
        $event = Event::factory()->create(['name' => 'Meu Evento']);

        $response = $this->actingAs($this->organizador())->get(route('admin.evento.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('evento.name', $event->name));
    }

    public function test_staff_updates_the_event(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'description' => 'Soluções para acesso à saúde',
            ]))
            ->assertRedirect(route('admin.evento.edit'))
            ->assertSessionHas('sucesso');

        $event = Event::current();
        $this->assertSame('Hackathon Atualizado', $event->name);
        $this->assertSame('Soluções para acesso à saúde', $event->description);
    }

    public function test_staff_sets_the_certificate_signer(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'certificate_signer_name' => 'Maria Souza',
                'certificate_signer_role' => 'Coordenadora do Hackathon',
            ]))
            ->assertSessionDoesntHaveErrors();

        $event = Event::current();
        $this->assertSame('Maria Souza', $event->certificate_signer_name);
        $this->assertSame('Coordenadora do Hackathon', $event->certificate_signer_role);
    }

    public function test_certificate_signer_role_is_required_when_name_is_filled(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'certificate_signer_name' => 'Maria Souza',
                'certificate_signer_role' => '',
            ]))
            ->assertSessionHasErrors('certificate_signer_role');
    }

    public function test_max_team_size_cannot_be_smaller_than_the_minimum(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'min_team_size' => 5,
                'max_team_size' => 2,
            ]))
            ->assertSessionHasErrors('max_team_size');
    }

    public function test_registration_window_must_close_after_it_opens(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos([
                'registration_opens_at' => now()->addDay()->toIso8601String(),
                'registration_closes_at' => now()->toIso8601String(),
            ]))
            ->assertSessionHasErrors('registration_closes_at');
    }

    public function test_does_not_touch_fields_managed_by_their_own_action(): void
    {
        $event = Event::factory()->create();
        $event->results_published_at = now();
        $event->judges_per_submission = 4;
        $event->save();

        $this->actingAs($this->organizador())
            ->patch(route('admin.evento.update'), $this->dadosValidos());

        $fresh = $event->fresh();
        $this->assertNotNull($fresh->results_published_at);
        $this->assertSame(4, $fresh->judges_per_submission);
    }

    public function test_a_participant_cannot_edit_the_event(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('admin.evento.edit'))
            ->assertForbidden();

        $this->actingAs($this->participante())
            ->patch(route('admin.evento.update'), $this->dadosValidos())
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.evento.edit'))->assertRedirect(route('login'));
    }

    public function test_staff_uploads_the_regulation_pdf(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.evento.regulamento.upload'), [
                'regulamento' => UploadedFile::fake()->create('edital-2026.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.evento.edit'))
            ->assertSessionHas('sucesso');

        $fresh = $event->fresh();
        $this->assertSame('edital-2026.pdf', $fresh->regulation_original_name);
        $this->assertNotNull($fresh->regulation_updated_at);
        Storage::disk('local')->assertExists($fresh->regulation_path);
    }

    /** Substituir troca o arquivo e apaga o antigo do disco -- não empilha lixo. */
    public function test_uploading_again_replaces_the_previous_file(): void
    {
        $event = Event::factory()->create();

        $this->actingAs($this->organizador())->post(route('admin.evento.regulamento.upload'), [
            'regulamento' => UploadedFile::fake()->create('primeira-versao.pdf', 100, 'application/pdf'),
        ]);
        $caminhoAntigo = $event->fresh()->regulation_path;

        $this->actingAs($this->organizador())->post(route('admin.evento.regulamento.upload'), [
            'regulamento' => UploadedFile::fake()->create('versao-final.pdf', 100, 'application/pdf'),
        ]);

        $fresh = $event->fresh();
        $this->assertSame('versao-final.pdf', $fresh->regulation_original_name);
        Storage::disk('local')->assertMissing($caminhoAntigo);
        Storage::disk('local')->assertExists($fresh->regulation_path);
    }

    public function test_a_file_type_outside_the_allowlist_is_refused(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->post(route('admin.evento.regulamento.upload'), [
                'regulamento' => UploadedFile::fake()->create('edital.docx', 100, 'application/msword'),
            ])
            ->assertSessionHasErrors('regulamento');

        $this->assertNull(Event::current()->regulation_path);
    }

    public function test_a_participant_cannot_upload_the_regulation(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->post(route('admin.evento.regulamento.upload'), [
                'regulamento' => UploadedFile::fake()->create('edital.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }
}
