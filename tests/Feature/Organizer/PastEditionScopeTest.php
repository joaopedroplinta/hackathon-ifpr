<?php

namespace Tests\Feature\Organizer;

use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Rubric;
use App\Models\ScheduleItem;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registro de edição anterior é histórico: as rotas de escrita do painel
 * respondem 404, como já fazem as de equipe e de atribuição de jurado.
 */
class PastEditionScopeTest extends TestCase
{
    use RefreshDatabase;

    private Event $antigo;

    private Event $atual;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->antigo = Event::factory()->create(['edition' => 1]);
        $this->atual = Event::factory()->create(['edition' => 2]);

        $organizador = User::factory()->create(['email_verified_at' => now()]);
        $organizador->assignRole(Role::Organizador->value);
        $this->actingAs($organizador);
    }

    public function test_criteria_of_a_past_edition_cannot_be_changed(): void
    {
        $rubrica = Rubric::factory()->create(['event_id' => $this->antigo->id]);
        $criterio = Criterion::factory()->create(['rubric_id' => $rubrica->id, 'name' => 'Original', 'weight' => 1]);

        $this->patch(route('painel.rubrica.criteria.update', $criterio), ['name' => 'Alterado', 'weight' => 9, 'max_score' => 10])
            ->assertNotFound();
        $this->delete(route('painel.rubrica.criteria.destroy', $criterio))->assertNotFound();
        $this->post(route('painel.rubrica.criteria.store', $rubrica), ['name' => 'Novo', 'weight' => 1, 'max_score' => 10])
            ->assertNotFound();

        $this->assertSame('Original', $criterio->fresh()->name);
        $this->assertSame(1, Criterion::where('rubric_id', $rubrica->id)->count());
    }

    public function test_a_rubric_of_a_past_edition_cannot_be_activated_deleted_or_shown(): void
    {
        $rubrica = Rubric::factory()->create(['event_id' => $this->antigo->id, 'is_active' => false]);

        $this->patch(route('painel.rubrica.activate', $rubrica))->assertNotFound();
        $this->delete(route('painel.rubrica.destroy', $rubrica))->assertNotFound();
        $this->get(route('painel.rubrica.show', $rubrica))->assertNotFound();

        $this->assertFalse($rubrica->fresh()->is_active);
        $this->assertNotNull(Rubric::find($rubrica->id));
    }

    public function test_schedule_items_of_a_past_edition_cannot_be_changed(): void
    {
        $item = ScheduleItem::factory()->create(['event_id' => $this->antigo->id, 'is_published' => false]);

        $this->get(route('painel.agenda.edit', $item))->assertNotFound();
        $this->patch(route('painel.agenda.publish', $item))->assertNotFound();
        $this->delete(route('painel.agenda.destroy', $item))->assertNotFound();

        $this->assertFalse($item->fresh()->is_published);
        $this->assertNotNull(ScheduleItem::find($item->id));
    }

    public function test_a_submitted_evaluation_of_a_past_edition_cannot_be_reopened(): void
    {
        $juiz = User::factory()->create(['email_verified_at' => now()]);
        $juiz->assignRole(Role::Jurado->value);
        $equipe = Team::factory()->for($this->antigo)->create();
        $submissao = Submission::factory()->for($this->antigo)->for($equipe)->enviada()->create();

        $atribuicao = new JudgeAssignment;
        $atribuicao->event_id = $this->antigo->id;
        $atribuicao->judge_id = $juiz->id;
        $atribuicao->submission_id = $submissao->id;
        $atribuicao->status = JudgeAssignmentStatus::Done;
        $atribuicao->assigned_at = now();
        $atribuicao->save();

        $avaliacao = new Evaluation;
        $avaliacao->assignment_id = $atribuicao->id;
        $avaliacao->status = EvaluationStatus::Submitted;
        $avaliacao->submitted_at = now();
        $avaliacao->save();

        $this->post(route('painel.jurados.reopen-evaluation', $atribuicao), ['reason' => 'Motivo com mais de dez caracteres'])
            ->assertNotFound();

        $this->assertSame(EvaluationStatus::Submitted, $avaliacao->fresh()->status);
    }

    public function test_the_current_edition_still_works(): void
    {
        $rubrica = Rubric::factory()->create(['event_id' => $this->atual->id]);
        $criterio = Criterion::factory()->create(['rubric_id' => $rubrica->id, 'name' => 'Original', 'weight' => 1]);
        $item = ScheduleItem::factory()->create(['event_id' => $this->atual->id, 'is_published' => false]);

        $this->patch(route('painel.rubrica.criteria.update', $criterio), ['name' => 'Alterado', 'weight' => 2, 'max_score' => 10])
            ->assertRedirect();
        $this->patch(route('painel.agenda.publish', $item))->assertRedirect();

        $this->assertSame('Alterado', $criterio->fresh()->name);
        $this->assertTrue($item->fresh()->is_published);
    }
}
