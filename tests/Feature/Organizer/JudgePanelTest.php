<?php

namespace Tests\Feature\Organizer;

use App\Enums\EvaluationStatus;
use App\Enums\Role;
use App\Models\ConflictOfInterest;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/** A tela de jurados do painel: o que ela mostra e as mensagens da distribuição. */
class JudgePanelTest extends TestCase
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

    private function jurado(string $nome): User
    {
        $user = User::factory()->create(['name' => $nome, 'email_verified_at' => now()]);
        $user->assignRole(Role::Jurado->value);

        return $user;
    }

    public function test_the_panel_lists_submissions_with_their_judges_and_evaluation_state(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 3]);
        $team = Team::factory()->for($event)->create(['name' => 'Os Devs']);
        $submissao = Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'App de Rotas']);
        $ana = $this->jurado('Ana');
        $bruno = $this->jurado('Bruno');

        $enviada = JudgeAssignment::factory()->create(['event_id' => $event->id, 'submission_id' => $submissao->id, 'judge_id' => $ana->id]);
        Evaluation::factory()->create(['assignment_id' => $enviada->id, 'status' => EvaluationStatus::Submitted]);
        JudgeAssignment::factory()->create(['event_id' => $event->id, 'submission_id' => $submissao->id, 'judge_id' => $bruno->id]);

        $this->actingAs($this->organizador())
            ->get(route('painel.jurados.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('admin/jurados/index')
                ->where('jurados_por_submissao', 3)
                ->where('submissoes.0.titulo', 'App de Rotas')
                ->where('submissoes.0.equipe', 'Os Devs')
                ->has('submissoes.0.jurados', 2)
                ->where('submissoes.0.jurados.0.avaliacao_enviada', true)
                ->where('submissoes.0.jurados.1.avaliacao_enviada', false)
                ->where('jurados.0.nome', 'Ana')
                ->where('jurados.0.total_atribuicoes', 1)
                ->where('opcoes.equipes.0.nome', 'Os Devs')
            );
    }

    public function test_a_draft_submission_does_not_enter_the_assignment_list(): void
    {
        $event = Event::factory()->create();
        Submission::factory()->for($event)->for(Team::factory()->for($event))->create();

        $this->actingAs($this->organizador())
            ->get(route('painel.jurados.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('submissoes', []));
    }

    public function test_the_panel_lists_conflicts_of_interest_with_judge_team_and_reason(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create(['name' => 'Os Devs']);
        ConflictOfInterest::factory()->create([
            'judge_id' => $this->jurado('Ana')->id,
            'team_id' => $team->id,
            'reason' => 'Orientadora',
        ]);

        $this->actingAs($this->organizador())
            ->get(route('painel.jurados.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('conflitos.0.jurado', 'Ana')
                ->where('conflitos.0.equipe', 'Os Devs')
                ->where('conflitos.0.motivo', 'Orientadora')
            );
    }

    public function test_staff_removes_a_conflict_of_interest(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $conflito = ConflictOfInterest::factory()->create(['judge_id' => $this->jurado('Ana')->id, 'team_id' => $team->id]);

        $this->actingAs($this->organizador())
            ->delete(route('painel.jurados.conflicts.destroy', $conflito))
            ->assertRedirect(route('painel.jurados.index'))
            ->assertSessionHas('sucesso', 'Conflito removido.');

        $this->assertModelMissing($conflito);
    }

    public function test_distribution_warns_which_submissions_found_no_eligible_judge(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        $team = Team::factory()->for($event)->create(['name' => 'Sem Jurado']);
        Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'Projeto Órfão']);
        // O único jurado tem conflito com a equipe: conflito bloqueia, não só avisa.
        ConflictOfInterest::factory()->create(['judge_id' => $this->jurado('Único')->id, 'team_id' => $team->id]);

        $this->actingAs($this->organizador())
            ->post(route('painel.jurados.distribute'))
            ->assertRedirect(route('painel.jurados.index'))
            ->assertSessionHas('erro', '0 atribuições criadas, mas faltou jurado elegível pra: Projeto Órfão.');
    }

    public function test_distribution_reports_how_many_assignments_were_created(): void
    {
        $event = Event::factory()->create(['judges_per_submission' => 1]);
        Submission::factory()->for($event)->for(Team::factory()->for($event))->enviada()->create();
        $this->jurado('Ana');

        $this->actingAs($this->organizador())
            ->post(route('painel.jurados.distribute'))
            ->assertSessionHas('sucesso', '1 atribuições criadas.');
    }
}
