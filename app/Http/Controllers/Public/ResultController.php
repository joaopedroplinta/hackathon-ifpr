<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Result;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Resultado público. Sem Policy: o que decide se algo aparece aqui é
 * `results_published_at`, checado no servidor -- nunca escondido só no
 * componente React (.claude/rules/security.md).
 */
class ResultController extends Controller
{
    public function show(): Response
    {
        $event = Event::current();

        if (! $event || ! $event->resultsArePublished()) {
            return Inertia::render('publico/resultados', [
                'publicado' => false,
                'evento' => $event ? ['nome' => $event->name] : null,
                'podio_geral' => [],
                'podio_por_trilha' => [],
                'premio_popular' => null,
            ]);
        }

        $resultados = Result::forEvent($event)->with('submission.team.track')->get();

        $podioGeral = $resultados
            ->filter(fn (Result $r) => $r->rank_overall !== null && $r->rank_overall <= 3)
            ->sortBy('rank_overall')
            ->map(fn (Result $r) => $this->linhaPodio($r, $r->rank_overall))
            ->values()
            ->all();

        $podioPorTrilha = $resultados
            // team->track pode ser nulo mesmo com rank_track preenchido --
            // ComputeResults já não deveria gerar isso, mas a tela não pode
            // cair por causa de uma linha velha/inconsistente.
            ->filter(fn (Result $r) => $r->rank_track !== null && $r->rank_track <= 3 && $r->submission->team->track !== null)
            ->sortBy('rank_track')
            ->groupBy(fn (Result $r) => $r->submission->team->track->name)
            ->map(fn ($grupo) => $grupo->map(fn (Result $r) => $this->linhaPodio($r, $r->rank_track, comTrilha: false))->values()->all())
            ->all();

        // Contagem de voto popular fica escondida enquanto a janela de
        // votação está aberta -- efeito manada (regras-avaliacao).
        $premioPopular = null;
        if (! $event->votingIsOpen()) {
            $vencedor = $resultados
                ->filter(fn (Result $r) => $r->popular_votes_count > 0)
                ->sortByDesc('popular_votes_count')
                ->first();

            if ($vencedor) {
                $premioPopular = [
                    'titulo' => $vencedor->submission->title ?? 'Sem título',
                    'equipe' => $vencedor->submission->team->name,
                    'votos' => $vencedor->popular_votes_count,
                ];
            }
        }

        return Inertia::render('publico/resultados', [
            'publicado' => true,
            'evento' => ['nome' => $event->name],
            'podio_geral' => $podioGeral,
            'podio_por_trilha' => $podioPorTrilha,
            'premio_popular' => $premioPopular,
        ]);
    }

    /**
     * @return array{posicao: int, titulo: string, equipe: string, nota_final: float, trilha: string|null}
     */
    private function linhaPodio(Result $r, int $posicao, bool $comTrilha = true): array
    {
        return [
            'posicao' => $posicao,
            'titulo' => $r->submission->title ?? 'Sem título',
            'equipe' => $r->submission->team->name,
            'nota_final' => (float) $r->final_score,
            'trilha' => $comTrilha ? $r->submission->team->track?->name : null,
        ];
    }
}
