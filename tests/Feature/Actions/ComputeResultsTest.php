<?php

namespace Tests\Feature\Actions;

use App\Actions\Results\ComputeResults;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Enums\SubmissionStatus;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\PopularVote;
use App\Models\Result;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\Team;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComputeResultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array<int, Criterion>
     */
    private function rubricaComPesos(Event $event, array $pesos): array
    {
        $rubric = Rubric::factory()->for($event)->ativa()->create();

        return collect($pesos)
            ->map(fn ($peso, $indice) => Criterion::factory()->for($rubric)->create([
                'weight' => number_format($peso, 2, '.', ''),
                'max_score' => 10,
                'position' => $indice,
            ]))
            ->all();
    }

    private function submissaoEnviada(Event $event, ?Track $track = null, ?string $submittedAt = null): Submission
    {
        $team = Team::factory()->for($event)->create(['track_id' => $track?->id]);

        return Submission::factory()->for($event)->for($team)->enviada()->create([
            'submitted_at' => $submittedAt ?? now()->subHour(),
        ]);
    }

    private function jurado(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Jurado->value);

        return $user;
    }

    /**
     * @param  array<int, float>  $notasPorCriterio  Na mesma ordem de $criterios.
     * @param  array<int, Criterion>  $criterios
     */
    private function avaliacaoSubmetida(Event $event, Submission $submissao, User $jurado, array $criterios, array $notasPorCriterio): Evaluation
    {
        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->status = JudgeAssignmentStatus::Done;
        $assignment->assigned_at = now();
        $assignment->save();

        $evaluation = new Evaluation;
        $evaluation->assignment_id = $assignment->id;
        $evaluation->status = EvaluationStatus::Submitted;
        $evaluation->submitted_at = now();
        $evaluation->save();

        foreach ($criterios as $indice => $criterio) {
            $score = new EvaluationScore;
            $score->evaluation_id = $evaluation->id;
            $score->criterion_id = $criterio->id;
            $score->score = $notasPorCriterio[$indice];
            $score->save();
        }

        return $evaluation;
    }

    /**
     * Caso conferido na mão (regras-avaliacao pede isto antes de mexer em
     * cálculo): 3 jurados atribuídos, 4 critérios de pesos diferentes
     * (soma 10), 1 jurado ausente (nunca enviou avaliação).
     *
     * Jurado A: [8, 7, 9, 6] -> (8*4+7*3+9*2+6*1)/10 = 77/10 = 7.70
     * Jurado B: [6, 8, 7, 10] -> (6*4+8*3+7*2+10*1)/10 = 72/10 = 7.20
     * Jurado C: ausente, não conta.
     * nota_final = (7.70 + 7.20) / 2 = 7.45
     */
    public function test_matches_a_hand_checked_case_with_three_judges_four_criteria_and_one_absent(): void
    {
        $event = Event::factory()->create();
        $criterios = $this->rubricaComPesos($event, [4, 3, 2, 1]);
        $submissao = $this->submissaoEnviada($event);

        $juradoA = $this->jurado();
        $juradoB = $this->jurado();
        $juradoAusente = $this->jurado();

        $this->avaliacaoSubmetida($event, $submissao, $juradoA, $criterios, [8, 7, 9, 6]);
        $this->avaliacaoSubmetida($event, $submissao, $juradoB, $criterios, [6, 8, 7, 10]);

        // Jurado ausente tem atribuição, mas nunca enviou avaliação.
        $assignmentAusente = new JudgeAssignment;
        $assignmentAusente->event_id = $event->id;
        $assignmentAusente->judge_id = $juradoAusente->id;
        $assignmentAusente->submission_id = $submissao->id;
        $assignmentAusente->assigned_at = now();
        $assignmentAusente->save();

        app(ComputeResults::class)->handle($event);

        $result = Result::forEvent($event)->where('submission_id', $submissao->id)->firstOrFail();

        $this->assertSame('7.45', (string) $result->final_score);
        $this->assertSame(1, $result->rank_overall);

        // assertEquals, não assertSame: o breakdown passa por json_encode/decode
        // (coluna json), e um float "redondo" como 7.0 volta como int(7) --
        // o valor bate, só o tipo PHP muda no round-trip.
        $breakdownPorCriterio = collect($result->criteria_breakdown['criterios'])->keyBy('criterio_id');
        $this->assertEquals(7.0, $breakdownPorCriterio[$criterios[0]->id]['media']);
        $this->assertEquals(7.5, $breakdownPorCriterio[$criterios[1]->id]['media']);
        $this->assertEquals(8.0, $breakdownPorCriterio[$criterios[2]->id]['media']);
        $this->assertEquals(8.0, $breakdownPorCriterio[$criterios[3]->id]['media']);
    }

    public function test_a_submission_with_no_submitted_evaluation_gets_a_null_score_and_no_rank(): void
    {
        $event = Event::factory()->create();
        $this->rubricaComPesos($event, [1]);
        $submissao = $this->submissaoEnviada($event);

        app(ComputeResults::class)->handle($event);

        $result = Result::forEvent($event)->where('submission_id', $submissao->id)->firstOrFail();

        $this->assertNull($result->final_score);
        $this->assertNull($result->rank_overall);
    }

    public function test_recomputing_updates_the_same_row_instead_of_duplicating(): void
    {
        $event = Event::factory()->create();
        $criterios = $this->rubricaComPesos($event, [1]);
        $submissao = $this->submissaoEnviada($event);
        $this->avaliacaoSubmetida($event, $submissao, $this->jurado(), $criterios, [5]);

        app(ComputeResults::class)->handle($event);
        $primeiraExecucao = Result::forEvent($event)->where('submission_id', $submissao->id)->firstOrFail();

        app(ComputeResults::class)->handle($event);

        $this->assertSame(1, Result::forEvent($event)->count());
        $this->assertSame($primeiraExecucao->id, Result::forEvent($event)->first()->id);
    }

    public function test_a_tie_is_broken_by_the_highest_weighted_criterion(): void
    {
        $event = Event::factory()->create();
        $criterios = $this->rubricaComPesos($event, [3, 1]);

        $submissaoA = $this->submissaoEnviada($event);
        $this->avaliacaoSubmetida($event, $submissaoA, $this->jurado(), $criterios, [8, 4]);
        // (8*3 + 4*1) / 4 = 28/4 = 7.00

        $submissaoB = $this->submissaoEnviada($event);
        $this->avaliacaoSubmetida($event, $submissaoB, $this->jurado(), $criterios, [6, 10]);
        // (6*3 + 10*1) / 4 = 28/4 = 7.00 -- mesma nota final, critério de maior peso decide

        app(ComputeResults::class)->handle($event);

        $resultA = Result::forEvent($event)->where('submission_id', $submissaoA->id)->firstOrFail();
        $resultB = Result::forEvent($event)->where('submission_id', $submissaoB->id)->firstOrFail();

        $this->assertSame('7.00', (string) $resultA->final_score);
        $this->assertSame('7.00', (string) $resultB->final_score);
        $this->assertSame(1, $resultA->rank_overall);
        $this->assertSame(2, $resultB->rank_overall);
    }

    public function test_a_true_tie_gets_the_same_ranking_and_the_next_position_is_skipped(): void
    {
        $event = Event::factory()->create();
        $criterios = $this->rubricaComPesos($event, [1]);

        $empateA = $this->submissaoEnviada($event, submittedAt: now()->subHours(2));
        $this->avaliacaoSubmetida($event, $empateA, $this->jurado(), $criterios, [7]);

        $empateB = $this->submissaoEnviada($event, submittedAt: now()->subHours(2));
        $this->avaliacaoSubmetida($event, $empateB, $this->jurado(), $criterios, [7]);

        $terceira = $this->submissaoEnviada($event);
        $this->avaliacaoSubmetida($event, $terceira, $this->jurado(), $criterios, [5]);

        app(ComputeResults::class)->handle($event);

        $resultA = Result::forEvent($event)->where('submission_id', $empateA->id)->firstOrFail();
        $resultB = Result::forEvent($event)->where('submission_id', $empateB->id)->firstOrFail();
        $resultTerceira = Result::forEvent($event)->where('submission_id', $terceira->id)->firstOrFail();

        $this->assertSame(1, $resultA->rank_overall);
        $this->assertSame(1, $resultB->rank_overall);
        $this->assertSame(3, $resultTerceira->rank_overall);
    }

    public function test_ranking_is_scoped_per_track_independently_of_the_overall_ranking(): void
    {
        $event = Event::factory()->create();
        $criterios = $this->rubricaComPesos($event, [1]);
        $trilhaA = Track::factory()->for($event)->create();
        $trilhaB = Track::factory()->for($event)->create();

        $melhorGeralNaTrilhaA = $this->submissaoEnviada($event, $trilhaA);
        $this->avaliacaoSubmetida($event, $melhorGeralNaTrilhaA, $this->jurado(), $criterios, [9]);

        $piorNaTrilhaB = $this->submissaoEnviada($event, $trilhaB);
        $this->avaliacaoSubmetida($event, $piorNaTrilhaB, $this->jurado(), $criterios, [3]);

        app(ComputeResults::class)->handle($event);

        $resultTrilhaB = Result::forEvent($event)->where('submission_id', $piorNaTrilhaB->id)->firstOrFail();

        // Última colocada no geral, mas é a única da própria trilha -- 1ª lá.
        $this->assertSame(2, $resultTrilhaB->rank_overall);
        $this->assertSame(1, $resultTrilhaB->rank_track);
    }

    /**
     * groupBy(null) vira a chave string "" em PHP -- um "if ($id === null)"
     * depois do groupBy nunca dispara. Sem o filtro antes de agrupar, uma
     * equipe sem trilha ganhava rank_track como se todas as equipes sem
     * trilha do evento fossem, juntas, "a trilha delas".
     */
    public function test_a_team_without_a_track_never_gets_a_track_rank(): void
    {
        $event = Event::factory()->create();
        $criterios = $this->rubricaComPesos($event, [1]);

        $semTrilha = $this->submissaoEnviada($event, track: null);
        $this->avaliacaoSubmetida($event, $semTrilha, $this->jurado(), $criterios, [9]);

        app(ComputeResults::class)->handle($event);

        $result = Result::forEvent($event)->where('submission_id', $semTrilha->id)->firstOrFail();

        $this->assertNotNull($result->rank_overall);
        $this->assertNull($result->rank_track);
    }

    public function test_a_draft_submission_is_excluded_from_the_computation(): void
    {
        $event = Event::factory()->create();
        $this->rubricaComPesos($event, [1]);
        $team = Team::factory()->for($event)->create();
        $rascunho = Submission::factory()->for($event)->for($team)->create(['status' => SubmissionStatus::Draft]);

        app(ComputeResults::class)->handle($event);

        $this->assertSame(0, Result::forEvent($event)->where('submission_id', $rascunho->id)->count());
    }

    public function test_popular_vote_counts_come_from_the_popular_votes_table(): void
    {
        $event = Event::factory()->create();
        $this->rubricaComPesos($event, [1]);
        $submissao = $this->submissaoEnviada($event);
        PopularVote::factory()->for($event)->for($submissao)->create();
        PopularVote::factory()->for($event)->for($submissao)->create();

        app(ComputeResults::class)->handle($event);

        $result = Result::forEvent($event)->where('submission_id', $submissao->id)->firstOrFail();
        $this->assertSame(2, $result->popular_votes_count);
    }
}
