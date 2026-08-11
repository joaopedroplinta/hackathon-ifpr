<?php

namespace App\Http\Controllers\Judge;

use App\Actions\Evaluation\SaveEvaluationDraft;
use App\Actions\Evaluation\SubmitEvaluation;
use App\Enums\EvaluationStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Judge\SaveEvaluationDraftRequest;
use App\Http\Requests\Judge\SubmitEvaluationRequest;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\Rubric;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $event = $this->currentEventOrFail();

        $assignments = JudgeAssignment::forEvent($event)
            ->forJudge(request()->user())
            ->with(['submission.team', 'evaluation'])
            ->get();

        $avaliadas = $assignments->filter(
            fn (JudgeAssignment $a) => $a->evaluation?->status === EvaluationStatus::Submitted
        )->count();

        return Inertia::render('jurado/fila', [
            'submissoes' => $assignments
                ->map(fn (JudgeAssignment $a) => [
                    'submission_id' => $a->submission_id,
                    'titulo' => $a->submission->title ?? 'Sem título',
                    'equipe' => $a->submission->team->name,
                    'enviada' => $a->evaluation?->status === EvaluationStatus::Submitted,
                ])
                ->values(),
            'progresso' => ['avaliadas' => $avaliadas, 'total' => $assignments->count()],
        ]);
    }

    public function show(Submission $submission): Response
    {
        $assignment = $this->assignmentFor($submission);
        $this->authorize('view', [Evaluation::class, $assignment]);

        $rubric = Rubric::forEvent($submission->event)->where('is_active', true)->with('criteria')->first();
        $evaluation = $assignment->evaluation()->with('scores')->first();
        $notasPorCriterio = $evaluation?->scores->keyBy('criterion_id') ?? collect();

        return Inertia::render('jurado/avaliar', [
            'submissao' => [
                'id' => $submission->id,
                'titulo' => $submission->title ?? 'Sem título',
                'equipe' => $submission->team->name,
                'resumo' => $submission->summary,
                'descricao' => $submission->description,
                'repo_url' => $submission->repo_url,
                'video_url' => $submission->video_url,
                'deploy_url' => $submission->deploy_url,
            ],
            'criterios' => $rubric
                ? $rubric->criteria
                    ->map(fn (Criterion $c) => [
                        'id' => $c->id,
                        'nome' => $c->name,
                        'descricao' => $c->description,
                        'peso' => (float) $c->weight,
                        'nota_maxima' => $c->max_score,
                    ])
                    ->all()
                : [],
            'avaliacao' => [
                'overall_comment' => $evaluation?->overall_comment,
                'notas' => $notasPorCriterio
                    ->map(fn (EvaluationScore $s) => [
                        'criterion_id' => $s->criterion_id,
                        'score' => $s->score !== null ? (float) $s->score : null,
                        'comment' => $s->comment,
                    ])
                    ->values(),
            ],
            'somente_leitura' => $evaluation?->status === EvaluationStatus::Submitted,
        ]);
    }

    public function autosave(SaveEvaluationDraftRequest $request, Submission $submission): RedirectResponse
    {
        $assignment = $this->assignmentFor($submission);
        $this->authorize('update', [Evaluation::class, $assignment]);

        app(SaveEvaluationDraft::class)->handle(
            $assignment,
            $request->validated('scores'),
            $request->validated('overall_comment')
        );

        return back();
    }

    public function submit(SubmitEvaluationRequest $request, Submission $submission): RedirectResponse
    {
        $assignment = $this->assignmentFor($submission);
        $this->authorize('update', [Evaluation::class, $assignment]);

        app(SubmitEvaluation::class)->handle(
            $assignment,
            $request->validated('scores'),
            $request->validated('overall_comment')
        );

        return to_route('jurado.index')->with('sucesso', 'Avaliação enviada.');
    }

    /** Escopado por judge_id -- jurado nunca acessa atribuição de outro (regras-avaliacao). */
    private function assignmentFor(Submission $submission): JudgeAssignment
    {
        $assignment = JudgeAssignment::query()
            ->forJudge(request()->user())
            ->where('submission_id', $submission->id)
            ->first();

        abort_if($assignment === null, 403, 'Esta submissão não foi atribuída a você.');

        return $assignment;
    }
}
