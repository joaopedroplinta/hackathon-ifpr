<?php

namespace App\Actions\Results;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\Result;
use App\Models\Rubric;
use App\Models\Submission;
use Illuminate\Support\Collection;

/**
 * Materializa o resultado de um evento (regras-avaliacao):
 *
 * - nota_avaliacao (um jurado, uma submissão) = Σ(score×peso)/Σ(peso), usando
 *   sempre os critérios que aquela avaliação de fato pontuou -- não a
 *   rubrica ativa "de agora". Uma rubrica trocada no meio do evento não pode
 *   zerar silenciosamente uma nota já enviada contra a rubrica anterior.
 * - nota_final = média simples das nota_avaliacao dos jurados que
 *   efetivamente submeteram. Nula se nenhum submeteu -- nunca zero.
 * - Desempate (nesta ordem, usando a rubrica ativa no momento do cálculo):
 *   maior nota no critério de maior peso -> maior nota no segundo critério
 *   de maior peso -> submissão mais cedo. Empate que sobrevive aos três
 *   recebe a mesma colocação.
 * - Idempotente: roda de novo e atualiza a mesma linha, nunca duplica.
 */
class ComputeResults
{
    public function handle(Event $event): void
    {
        $rubricaAtiva = Rubric::forEvent($event)->where('is_active', true)->with('criteria')->first();

        $agora = now();

        $linhas = Submission::forEvent($event)
            ->with('team')
            ->get()
            ->filter(fn (Submission $s) => $s->status->countsForEvaluation())
            ->map(fn (Submission $submissao) => [
                'submissao' => $submissao,
                ...$this->calcularNotaFinal($submissao),
            ])
            ->values();

        $ordenadas = $this->ordenarComDesempate($linhas, $rubricaAtiva);
        $ranksGerais = $this->atribuirRanks($ordenadas, $rubricaAtiva);

        $ranksPorTrilha = [];
        foreach ($ordenadas->groupBy(fn (array $linha) => $linha['submissao']->team->track_id) as $trilhaId => $grupo) {
            if ($trilhaId === null) {
                continue;
            }

            $ranksPorTrilha += $this->atribuirRanks($grupo->values(), $rubricaAtiva);
        }

        foreach ($ordenadas as $linha) {
            $submissao = $linha['submissao'];

            $result = Result::query()
                ->where('event_id', $event->id)
                ->where('submission_id', $submissao->id)
                ->first() ?? new Result;

            $result->event_id = $event->id;
            $result->submission_id = $submissao->id;
            $result->final_score = $linha['nota_final'];
            $result->criteria_breakdown = $linha['breakdown'];
            $result->rank_overall = $ranksGerais[$submissao->id] ?? null;
            $result->rank_track = $ranksPorTrilha[$submissao->id] ?? null;
            // popular_votes_count fica em 0 até a tabela popular_votes existir
            // (voto popular é o próximo slice desta sprint).
            $result->popular_votes_count = $result->popular_votes_count ?? 0;
            $result->computed_at = $agora;
            $result->save();
        }
    }

    /**
     * @return array{nota_final: float|null, breakdown: array{jurados: array<int, array{jurado_id: int, nota_avaliacao: float}>, criterios: array<int, array{criterio_id: int, nome: string, peso: float, media: float}>}}
     */
    private function calcularNotaFinal(Submission $submissao): array
    {
        $avaliacoesEnviadas = Evaluation::query()
            ->whereHas('assignment', fn ($q) => $q->where('submission_id', $submissao->id))
            ->where('status', EvaluationStatus::Submitted)
            ->with(['scores.criterion', 'assignment'])
            ->get();

        if ($avaliacoesEnviadas->isEmpty()) {
            return ['nota_final' => null, 'breakdown' => ['jurados' => [], 'criterios' => []]];
        }

        $notasPorJurado = [];
        $porCriterio = [];

        foreach ($avaliacoesEnviadas as $avaliacao) {
            $pesoTotal = 0.0;
            $somaPonderada = 0.0;

            foreach ($avaliacao->scores as $nota) {
                $peso = (float) $nota->criterion->weight;
                $score = (float) $nota->score;

                $somaPonderada += $score * $peso;
                $pesoTotal += $peso;

                $porCriterio[$nota->criterion_id] ??= [
                    'nome' => $nota->criterion->name,
                    'peso' => $peso,
                    'soma' => 0.0,
                    'contagem' => 0,
                ];
                $porCriterio[$nota->criterion_id]['soma'] += $score;
                $porCriterio[$nota->criterion_id]['contagem']++;
            }

            $notasPorJurado[] = [
                'jurado_id' => $avaliacao->assignment->judge_id,
                'nota_avaliacao' => $pesoTotal > 0 ? round($somaPonderada / $pesoTotal, 2) : 0.0,
            ];
        }

        $notaFinal = round(collect($notasPorJurado)->avg('nota_avaliacao'), 2);

        $breakdown = [
            'jurados' => $notasPorJurado,
            'criterios' => collect($porCriterio)
                ->map(fn (array $c, int $criterioId) => [
                    'criterio_id' => $criterioId,
                    'nome' => $c['nome'],
                    'peso' => $c['peso'],
                    'media' => round($c['soma'] / $c['contagem'], 2),
                ])
                ->values()
                ->all(),
        ];

        return ['nota_final' => $notaFinal, 'breakdown' => $breakdown];
    }

    /**
     * @param  Collection<int, array{submissao: Submission, nota_final: float|null, breakdown: array}>  $linhas
     * @return Collection<int, array{submissao: Submission, nota_final: float|null, breakdown: array}>
     */
    private function ordenarComDesempate(Collection $linhas, ?Rubric $rubricaAtiva): Collection
    {
        [$primeiroCriterioId, $segundoCriterioId] = $this->doisMaioresPesos($rubricaAtiva);

        // Collection::sortBy() com array de [closure, 'desc'] não respeita a
        // direção quando a chave é uma closure -- só é confiável com string
        // ou dot-notation. Comparador explícito evita essa pegadinha.
        return $linhas
            ->sort(function (array $a, array $b) use ($primeiroCriterioId, $segundoCriterioId) {
                $notas = ($b['nota_final'] ?? -1) <=> ($a['nota_final'] ?? -1);
                if ($notas !== 0) {
                    return $notas;
                }

                $primeiro = $this->mediaDoCriterio($b, $primeiroCriterioId) <=> $this->mediaDoCriterio($a, $primeiroCriterioId);
                if ($primeiro !== 0) {
                    return $primeiro;
                }

                $segundo = $this->mediaDoCriterio($b, $segundoCriterioId) <=> $this->mediaDoCriterio($a, $segundoCriterioId);
                if ($segundo !== 0) {
                    return $segundo;
                }

                $submittedA = $a['submissao']->submitted_at?->timestamp ?? PHP_INT_MAX;
                $submittedB = $b['submissao']->submitted_at?->timestamp ?? PHP_INT_MAX;

                return $submittedA <=> $submittedB;
            })
            ->values();
    }

    /**
     * Ranking "1224": empate que sobrevive aos três critérios de desempate
     * fica com a mesma colocação, e a próxima posição pula o número que
     * faltou -- não é a média nem o "1223" (regras-avaliacao).
     *
     * @param  Collection<int, array{submissao: Submission, nota_final: float|null, breakdown: array}>  $ordenadas  Já ordenada por ordenarComDesempate().
     * @return array<int, int> submission_id => posição
     */
    private function atribuirRanks(Collection $ordenadas, ?Rubric $rubricaAtiva): array
    {
        [$primeiroCriterioId, $segundoCriterioId] = $this->doisMaioresPesos($rubricaAtiva);

        $chaveDe = fn (array $l) => [
            $l['nota_final'],
            $this->mediaDoCriterio($l, $primeiroCriterioId),
            $this->mediaDoCriterio($l, $segundoCriterioId),
        ];

        $ranks = [];
        $chaveAnterior = null;
        $idAnterior = null;

        foreach ($ordenadas->values() as $indice => $linha) {
            if ($linha['nota_final'] === null) {
                continue;
            }

            $chaveAtual = $chaveDe($linha);
            $mesmoQueAnterior = $chaveAnterior !== null && $chaveAtual === $chaveAnterior;

            $ranks[$linha['submissao']->id] = $mesmoQueAnterior ? $ranks[$idAnterior] : $indice + 1;

            $chaveAnterior = $chaveAtual;
            $idAnterior = $linha['submissao']->id;
        }

        return $ranks;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function doisMaioresPesos(?Rubric $rubricaAtiva): array
    {
        $ordenados = $rubricaAtiva ? $rubricaAtiva->criteria->sortByDesc('weight')->values() : collect();

        return [$ordenados->get(0)?->id, $ordenados->get(1)?->id];
    }

    private function mediaDoCriterio(array $linha, ?int $criterioId): float
    {
        if ($criterioId === null) {
            return 0.0;
        }

        $entrada = collect($linha['breakdown']['criterios'])->firstWhere('criterio_id', $criterioId);

        return $entrada['media'] ?? 0.0;
    }
}
