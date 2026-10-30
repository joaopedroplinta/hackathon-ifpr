<?php

namespace Tests\Feature\Organizer;

use App\Enums\CertificateType;
use App\Enums\IncidentKind;
use App\Enums\Role;
use App\Models\Certificate;
use App\Models\Criterion;
use App\Models\EvaluationScore;
use App\Models\Event;
use App\Models\Incident;
use App\Models\Rubric;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * As telas de listagem do painel: o que o front recebe em props (nomes em
 * português, datas em America/Sao_Paulo, ordem) e o que a Policy barra.
 */
class PanelListingsTest extends TestCase
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

    public function test_incident_listing_shows_history_newest_first_with_sao_paulo_dates(): void
    {
        $event = Event::factory()->create(['submission_deadline' => '2026-05-10 21:00:00']);
        $autor = User::factory()->create(['name' => 'Carlos']);
        Incident::factory()->for($event)->create([
            'declared_by' => $autor->id,
            'kind' => IncidentKind::Rede,
            'description' => 'Antigo',
            'started_at' => '2026-05-10 12:00:00',
        ]);
        Incident::factory()->for($event)->create([
            'declared_by' => $autor->id,
            'description' => 'Recente',
            'started_at' => '2026-05-10 15:30:00',
            'deadline_extension_minutes' => 30,
        ]);

        $this->actingAs($this->organizador())
            ->get(route('painel.incidentes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/incidentes/index')
                ->where('incidentes.0.descricao', 'Recente')
                ->where('incidentes.0.declarado_por', 'Carlos')
                ->where('incidentes.0.declarado_em', '10/05/2026 12:30')
                ->where('incidentes.0.extensao_minutos', 30)
                ->where('incidentes.1.descricao', 'Antigo')
                ->where('prazo_original', '10/05/2026 18:00')
                ->has('tipos', count(IncidentKind::cases()))
            );
    }

    public function test_incident_listing_is_empty_without_incidents(): void
    {
        Event::factory()->create(['submission_deadline' => null]);

        $this->actingAs($this->organizador())
            ->get(route('painel.incidentes.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('incidentes', [])
                ->where('prazo_original', null)
            );
    }

    public function test_a_participant_cannot_see_the_incident_listing(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('painel.incidentes.index'))
            ->assertForbidden();
    }

    public function test_certificate_listing_shows_issued_certificates_and_everyone_who_can_receive_one(): void
    {
        $event = Event::factory()->create();
        $ana = User::factory()->create(['name' => 'Ana Lima']);
        User::factory()->create(['name' => 'Bruno Sem Inscrição']);
        Certificate::factory()->for($event)->for($ana)->pronto()->create([
            'type' => CertificateType::Participacao,
            'issued_at' => '2026-05-10 15:30:00',
        ]);
        Certificate::factory()->for($event)->for($ana)->create(['type' => CertificateType::Mentor]);

        $this->actingAs($this->organizador())
            ->get(route('painel.certificados.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/certificados/index')
                ->has('certificados', 2)
                ->where('certificados.0.nome', 'Ana Lima')
                ->has('pessoas', 3)
                ->has('tipos', count(CertificateType::cases()))
            );
    }

    public function test_certificate_listing_flags_whether_the_pdf_is_ready(): void
    {
        $event = Event::factory()->create();
        Certificate::factory()->for($event)->pronto()->create(['issued_at' => '2026-05-10 15:30:00']);

        $this->actingAs($this->organizador())
            ->get(route('painel.certificados.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('certificados.0.pronto', true)
                ->where('certificados.0.emitido_em', '10/05/2026 12:30')
            );
    }

    public function test_a_participant_cannot_see_the_certificate_listing(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('painel.certificados.index'))
            ->assertForbidden();
    }

    public function test_rubric_listing_puts_the_active_one_first_with_criteria_count_and_weight_sum(): void
    {
        $event = Event::factory()->create();
        $inativa = Rubric::factory()->for($event)->create(['name' => 'A Rascunho', 'is_active' => false]);
        $ativa = Rubric::factory()->for($event)->create(['name' => 'B Oficial', 'is_active' => true]);
        Criterion::factory()->for($ativa)->create(['weight' => '2.00']);
        Criterion::factory()->for($ativa)->create(['weight' => '1.50']);

        $this->actingAs($this->organizador())
            ->get(route('painel.rubrica.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/rubrica/index')
                ->where('rubricas.0.nome', 'B Oficial')
                ->where('rubricas.0.ativa', true)
                ->where('rubricas.0.total_criterios', 2)
                ->where('rubricas.0.soma_pesos', 3.5)
                ->where('rubricas.1.nome', $inativa->name)
                ->where('rubricas.1.soma_pesos', 0)
            );
    }

    public function test_rubric_detail_lists_its_criteria(): void
    {
        $event = Event::factory()->create();
        $rubrica = Rubric::factory()->for($event)->create(['name' => 'Oficial']);
        Criterion::factory()->for($rubrica)->create(['name' => 'Inovação', 'weight' => '2.00', 'max_score' => 10]);

        $this->actingAs($this->organizador())
            ->get(route('painel.rubrica.show', $rubrica))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/rubrica/mostrar')
                ->where('rubrica.nome', 'Oficial')
                ->where('criterios.0.nome', 'Inovação')
                ->where('criterios.0.peso', 2)
                ->where('criterios.0.nota_maxima', 10)
            );
    }

    public function test_a_rubric_from_another_event_is_not_reachable(): void
    {
        // O evento atual é o de maior edição; a rubrica é de uma edição anterior.
        Event::factory()->create(['edition' => 5]);
        $outra = Rubric::factory()->for(Event::factory()->create(['edition' => 1]))->create();

        $this->actingAs($this->organizador())
            ->get(route('painel.rubrica.show', $outra))
            ->assertNotFound();
    }

    public function test_staff_activating_a_rubric_deactivates_the_others(): void
    {
        $event = Event::factory()->create();
        $antiga = Rubric::factory()->for($event)->create(['is_active' => true]);
        $nova = Rubric::factory()->for($event)->create(['is_active' => false]);

        $this->actingAs($this->organizador())
            ->patch(route('painel.rubrica.activate', $nova))
            ->assertRedirect(route('painel.rubrica.index'));

        $this->assertTrue($nova->fresh()->is_active);
        $this->assertFalse($antiga->fresh()->is_active);
    }

    public function test_staff_deletes_a_rubric(): void
    {
        $event = Event::factory()->create();
        $rubrica = Rubric::factory()->for($event)->create();

        $this->actingAs($this->organizador())
            ->delete(route('painel.rubrica.destroy', $rubrica))
            ->assertRedirect(route('painel.rubrica.index'));

        $this->assertNull(Rubric::find($rubrica->id));
    }

    public function test_a_criterion_with_a_recorded_score_cannot_be_removed(): void
    {
        $score = EvaluationScore::factory()->create();
        $criterio = $score->criterion;
        $rubrica = $criterio->rubric;
        // A factory da nota cria eventos soltos pelo caminho; este precisa ser o atual.
        $rubrica->event->update(['edition' => 99]);

        $this->actingAs($this->organizador())
            ->from(route('painel.rubrica.show', $rubrica))
            ->delete(route('painel.rubrica.criteria.destroy', $criterio))
            ->assertRedirect(route('painel.rubrica.show', $rubrica))
            ->assertSessionHas('erro');

        $this->assertNotNull(Criterion::find($criterio->id));
    }
}
