<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Submissions\RecordExternalSubmission;
use App\Enums\SubmissionSource;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\RecordSubmissionRequest;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Degraus 3 e 4 do plano B (PLANO.md, Anexo A.4): a equipe não conseguiu
 * usar o formulário web de jeito nenhum, e a organização lança em nome
 * dela a partir do que recebeu por e-mail ou no papel.
 */
class ManualSubmissionController extends Controller
{
    use ResolvesParticipation;

    public function create(): Response
    {
        $this->authorize('recordManually', Submission::class);

        $event = $this->currentEventOrFail();

        $equipes = Team::forEvent($event)
            ->with('submission')
            ->get()
            ->filter(fn (Team $team) => ! $team->submission?->isSubmitted())
            ->map(fn (Team $team) => ['id' => $team->id, 'nome' => $team->name])
            ->values()
            ->all();

        return Inertia::render('admin/submissoes/lancar', [
            'equipes' => $equipes,
            'fontes' => [
                ['value' => SubmissionSource::Email->value, 'label' => SubmissionSource::Email->label()],
                ['value' => SubmissionSource::Manual->value, 'label' => SubmissionSource::Manual->label()],
            ],
        ]);
    }

    public function store(RecordSubmissionRequest $request, RecordExternalSubmission $record): RedirectResponse
    {
        $this->authorize('recordManually', Submission::class);

        $event = $this->currentEventOrFail();
        $team = Team::forEvent($event)->findOrFail($request->integer('team_id'));
        $source = SubmissionSource::from($request->string('source')->value());

        $submission = $record->handle(
            $team,
            [
                'title' => $request->input('title'),
                'summary' => $request->input('summary'),
                'repo_url' => $request->string('repo_url')->value(),
                'video_url' => $request->input('video_url'),
            ],
            $source,
            $request->user(),
            // ->utc(): mesma cautela de ImportSubmissionsFromCsv -- Carbon
            // com fuso diferente de UTC perde o offset ao ser gravado pelo
            // cast do Eloquent. O front sempre manda ISO em UTC, mas não é
            // o único chamador possível deste endpoint.
            Carbon::parse($request->string('original_submitted_at')->value())->utc(),
        );

        if ($submission === null) {
            return back()->withErrors(['team_id' => 'Esta equipe já tem uma submissão registrada -- não é possível lançar por cima.']);
        }

        return to_route('admin.submissions.index')->with('sucesso', "Submissão de \"{$team->name}\" lançada e marcada pra conferência.");
    }
}
