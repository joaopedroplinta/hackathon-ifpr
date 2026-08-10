<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Submission;
use App\Models\Team;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SubmissionIndexTest extends TestCase
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

    private function submissao(Event $event, string $equipe, ?Track $track = null): Submission
    {
        $team = Team::factory()->for($event)->create([
            'name' => $equipe,
            'track_id' => $track?->id,
        ]);

        return Submission::factory()->for($event)->for($team)->enviada()->create();
    }

    public function test_staff_sees_every_submission_of_the_current_event(): void
    {
        $event = Event::factory()->create();
        $this->submissao($event, 'Equipe Alfa');
        $this->submissao($event, 'Equipe Beta');

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('admin/submissoes/index')
                    ->has('submissoes.data', 2)
                    ->where('resumo.total', 2)
            );
    }

    public function test_a_participant_cannot_open_the_panel(): void
    {
        Event::factory()->create();

        $participante = User::factory()->create(['email_verified_at' => now()]);
        $participante->assignRole(Role::Participante->value);

        $this->actingAs($participante)
            ->get(route('admin.submissions.index'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.submissions.index'))->assertRedirect(route('login'));
    }

    /**
     * Escopo de evento: sem ele o painel mostra a edição passada
     * (.claude/rules/database.md).
     */
    public function test_submissions_from_another_event_never_appear(): void
    {
        $atual = Event::factory()->create(['edition' => 2]);
        $anterior = Event::factory()->create(['edition' => 1]);

        $this->submissao($atual, 'Equipe Deste Ano');
        $this->submissao($anterior, 'Equipe Do Ano Passado');

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('submissoes.data', 1)
                    ->where('submissoes.data.0.equipe.nome', 'Equipe Deste Ano')
            );
    }

    public function test_the_status_filter_narrows_the_list(): void
    {
        $event = Event::factory()->create();

        $noPrazo = Team::factory()->for($event)->create(['name' => 'No Prazo']);
        Submission::factory()->for($event)->for($noPrazo)->enviada()->create();

        $atrasada = Team::factory()->for($event)->create(['name' => 'Atrasada']);
        Submission::factory()->for($event)->for($atrasada)->atrasada()->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index', ['status' => 'late']))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('submissoes.data', 1)
                    ->where('submissoes.data.0.equipe.nome', 'Atrasada')
                    ->where('filtros.status', 'late')
            );
    }

    public function test_the_track_filter_narrows_the_list(): void
    {
        $event = Event::factory()->create();
        $saude = Track::factory()->for($event)->create(['name' => 'Saúde']);
        $educacao = Track::factory()->for($event)->create(['name' => 'Educação']);

        $this->submissao($event, 'Equipe Saúde', $saude);
        $this->submissao($event, 'Equipe Educação', $educacao);

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index', ['track_id' => $saude->id]))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('submissoes.data', 1)
                    ->where('submissoes.data.0.equipe.nome', 'Equipe Saúde')
            );
    }

    public function test_an_invalid_status_is_a_validation_error_not_an_empty_list(): void
    {
        $event = Event::factory()->create();
        $this->submissao($event, 'Equipe Alfa');

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index', ['status' => 'inventado']))
            ->assertSessionHasErrors('status');
    }

    /** Trilha de outra edição não pode filtrar a lista desta. */
    public function test_a_track_from_another_event_is_rejected(): void
    {
        Event::factory()->create(['edition' => 2]);
        $outro = Event::factory()->create(['edition' => 1]);
        $trilhaAlheia = Track::factory()->for($outro)->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index', ['track_id' => $trilhaAlheia->id]))
            ->assertSessionHasErrors('track_id');
    }

    public function test_the_search_matches_team_name_and_project_title(): void
    {
        $event = Event::factory()->create();

        $team = Team::factory()->for($event)->create(['name' => 'Os Devs']);
        Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'Alerta de enchente']);

        $outra = Team::factory()->for($event)->create(['name' => 'Outra Equipe']);
        Submission::factory()->for($event)->for($outra)->enviada()->create(['title' => 'Horta comunitária']);

        $organizador = $this->organizador();

        $this->actingAs($organizador)
            ->get(route('admin.submissions.index', ['busca' => 'os devs']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('submissoes.data', 1)
                ->where('submissoes.data.0.equipe.nome', 'Os Devs'));

        $this->actingAs($organizador)
            ->get(route('admin.submissions.index', ['busca' => 'enchente']))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('submissoes.data', 1)
                ->where('submissoes.data.0.titulo', 'Alerta de enchente'));
    }

    /**
     * A pergunta que o organizador faz na véspera do prazo. Rascunho não conta
     * como envio -- a equipe que só salvou rascunho ainda não entregou.
     */
    public function test_teams_without_a_submitted_project_are_listed(): void
    {
        $event = Event::factory()->create();

        $this->submissao($event, 'Já Enviou');

        Team::factory()->for($event)->create(['name' => 'Nem Começou']);

        $soRascunho = Team::factory()->for($event)->create(['name' => 'Só Rascunho']);
        Submission::factory()->for($event)->for($soRascunho)->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->has('resumo.equipes_sem_envio', 2)
                    ->where('resumo.equipes_sem_envio', ['Nem Começou', 'Só Rascunho'])
            );
    }

    /** Submissão que entrou por fora do sistema fica marcada -- Anexo A. */
    public function test_a_submission_recorded_outside_the_system_is_flagged(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create(['name' => 'Prancheta']);
        Submission::factory()->for($event)->for($team)->manual()->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.index'))
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('submissoes.data.0.precisa_conferencia', true)
                    ->where('submissoes.data.0.origem_label', 'Lançamento manual')
            );
    }
}
