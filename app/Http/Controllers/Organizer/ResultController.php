<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Results\ComputeResults;
use App\Actions\Results\FindResultPendencies;
use App\Actions\Results\PublishResults;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\PublishResultsRequest;
use App\Models\Result;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $this->authorize('viewAny', Result::class);

        $event = $this->currentEventOrFail();

        $resultsCollection = Result::forEvent($event)
            ->with(['submission.team.track'])
            ->orderByRaw('rank_overall IS NULL, rank_overall')
            ->get();

        return Inertia::render('admin/resultados/index', [
            'resultados' => $resultsCollection
                ->map(fn (Result $r) => [
                    'submission_id' => $r->submission_id,
                    'titulo' => $r->submission->title ?? 'Sem título',
                    'equipe' => $r->submission->team->name,
                    'trilha' => $r->submission->team->track?->name,
                    'nota_final' => $r->final_score !== null ? (float) $r->final_score : null,
                    'rank_overall' => $r->rank_overall,
                    'rank_track' => $r->rank_track,
                ])
                ->values()
                ->all(),
            'pendencias' => app(FindResultPendencies::class)->handle($event),
            'publicado_em' => $event->results_published_at?->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i'),
            'computado_em' => $resultsCollection->max('computed_at')?->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i'),
        ]);
    }

    public function recompute(): RedirectResponse
    {
        $this->authorize('recompute', Result::class);

        $event = $this->currentEventOrFail();

        app(ComputeResults::class)->handle($event);

        return to_route('painel.resultados.index')->with('sucesso', 'Resultados recalculados.');
    }

    /**
     * Ação manual e explícita (PLANO.md §7) -- calcular nunca publica sozinho.
     * Com pendência, exige confirmação explícita: o front manda
     * confirmar_pendencias só depois que o organizador vê o aviso e clica
     * de novo.
     */
    public function publish(PublishResultsRequest $request): RedirectResponse
    {
        $this->authorize('publish', Result::class);

        $event = $this->currentEventOrFail();

        $pendencias = app(FindResultPendencies::class)->handle($event);
        $temPendencia = ! empty($pendencias['submissoes_sem_nota'])
            || ! empty($pendencias['jurados_incompletos'])
            || ! empty($pendencias['empates']);

        if ($temPendencia && ! $request->boolean('confirmar_pendencias')) {
            return back()->with('erro', 'Há pendências antes de publicar -- confira a lista e confirme se quer publicar mesmo assim.');
        }

        app(PublishResults::class)->handle($event);

        return to_route('painel.resultados.index')->with('sucesso', 'Resultado publicado.');
    }
}
