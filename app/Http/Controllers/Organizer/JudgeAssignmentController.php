<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Judging\DistributeJudges;
use App\Enums\Role;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreConflictOfInterestRequest;
use App\Http\Requests\Organizer\StoreJudgeAssignmentRequest;
use App\Models\ConflictOfInterest;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Atribuição de jurados. A distribuição automática é sugestão -- o
 * organizador ajusta na mão e uma nova rodada de distribuição nunca
 * sobrescreve o que já existe, automático ou manual (regras-avaliacao).
 */
class JudgeAssignmentController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $this->authorize('viewAny', JudgeAssignment::class);

        $event = $this->currentEventOrFail();

        $submissoes = Submission::forEvent($event)
            ->with(['team:id,name', 'assignments' => fn ($q) => $q->with('judge:id,name')])
            ->get()
            ->filter(fn (Submission $s) => $s->status->countsForEvaluation())
            ->map(fn (Submission $submissao) => [
                'id' => $submissao->id,
                'titulo' => $submissao->title ?? 'Sem título',
                'equipe' => $submissao->team->name,
                'jurados' => $submissao->assignments
                    ->map(fn (JudgeAssignment $a) => [
                        'atribuicao_id' => $a->id,
                        'jurado_id' => $a->judge_id,
                        'nome' => $a->judge->name,
                        'status_label' => $a->status->label(),
                    ])
                    ->all(),
            ])
            ->values()
            ->all();

        $jurados = User::role(Role::Jurado->value)
            ->withCount(['judgeAssignments' => fn ($q) => $q->forEvent($event)])
            ->orderBy('name')
            ->get()
            ->map(fn (User $jurado) => [
                'id' => $jurado->id,
                'nome' => $jurado->name,
                'total_atribuicoes' => $jurado->judge_assignments_count,
            ])
            ->all();

        $conflitos = ConflictOfInterest::query()
            ->whereIn('judge_id', User::role(Role::Jurado->value)->pluck('id'))
            ->whereHas('team', fn ($q) => $q->forEvent($event))
            ->with(['judge:id,name', 'team:id,name'])
            ->get()
            ->map(fn (ConflictOfInterest $c) => [
                'id' => $c->id,
                'jurado' => $c->judge->name,
                'equipe' => $c->team->name,
                'motivo' => $c->reason,
            ])
            ->all();

        return Inertia::render('admin/jurados/index', [
            'submissoes' => $submissoes,
            'jurados' => $jurados,
            'conflitos' => $conflitos,
            'jurados_por_submissao' => $event->judges_per_submission,
            'opcoes' => [
                'equipes' => Team::forEvent($event)->orderBy('name')->get(['id', 'name'])
                    ->map(fn (Team $team) => ['id' => $team->id, 'nome' => $team->name])
                    ->all(),
            ],
        ]);
    }

    public function distribute(): RedirectResponse
    {
        $this->authorize('create', JudgeAssignment::class);

        $event = $this->currentEventOrFail();

        $resultado = app(DistributeJudges::class)->handle($event);

        if (! empty($resultado['sem_jurado_elegivel'])) {
            $lista = implode(', ', $resultado['sem_jurado_elegivel']);

            return to_route('admin.jurados.index')->with(
                'erro',
                "{$resultado['criadas']} atribuições criadas, mas faltou jurado elegível pra: {$lista}."
            );
        }

        return to_route('admin.jurados.index')->with('sucesso', "{$resultado['criadas']} atribuições criadas.");
    }

    public function store(StoreJudgeAssignmentRequest $request): RedirectResponse
    {
        $this->authorize('create', JudgeAssignment::class);

        $event = $this->currentEventOrFail();

        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $request->validated('judge_id');
        $assignment->submission_id = $request->validated('submission_id');
        $assignment->assigned_at = now();
        $assignment->save();

        return to_route('admin.jurados.index')->with('sucesso', 'Jurado atribuído.');
    }

    public function destroy(JudgeAssignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return to_route('admin.jurados.index')->with('sucesso', 'Atribuição removida.');
    }

    /**
     * Jurado ausente, em 1 clique: remove a atribuição dele e já preenche a
     * vaga que abriu, sem reprocessar o evento inteiro.
     */
    public function reassign(JudgeAssignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $event = $assignment->event;
        $submission = $assignment->submission;

        DB::transaction(function () use ($assignment, $event, $submission) {
            $assignment->delete();
            app(DistributeJudges::class)->handle($event, collect([$submission]));
        });

        return to_route('admin.jurados.index')->with('sucesso', 'Vaga reatribuída.');
    }

    public function storeConflict(StoreConflictOfInterestRequest $request): RedirectResponse
    {
        $this->authorize('create', ConflictOfInterest::class);

        $conflict = new ConflictOfInterest;
        $conflict->judge_id = $request->validated('judge_id');
        $conflict->team_id = $request->validated('team_id');
        $conflict->reason = $request->validated('reason');
        $conflict->save();

        return to_route('admin.jurados.index')->with('sucesso', 'Conflito de interesse registrado.');
    }

    public function destroyConflict(ConflictOfInterest $conflict): RedirectResponse
    {
        $this->authorize('delete', ConflictOfInterest::class);

        $conflict->delete();

        return to_route('admin.jurados.index')->with('sucesso', 'Conflito removido.');
    }

    /** Único campo de configuração do evento que este sprint precisa. */
    public function updateJudgesPerSubmission(Request $request): RedirectResponse
    {
        $this->authorize('create', JudgeAssignment::class);

        $event = $this->currentEventOrFail();

        $data = $request->validate([
            'judges_per_submission' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $event->judges_per_submission = $data['judges_per_submission'];
        $event->save();

        return to_route('admin.jurados.index')->with('sucesso', 'Quantidade de jurados por submissão atualizada.');
    }
}
