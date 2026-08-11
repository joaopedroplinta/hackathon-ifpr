<?php

namespace App\Actions\Results;

use App\Enums\EvaluationStatus;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Result;
use App\Models\Submission;

/**
 * O que o painel do organizador mostra antes de publicar: submissões sem
 * nota, jurados incompletos, empates pendentes (PLANO.md, seção 4). Não
 * depende de já ter rodado ComputeResults -- funciona a partir das tabelas
 * fonte, então o painel mostra o estado real mesmo se nunca recalculou.
 */
class FindResultPendencies
{
    /**
     * @return array{submissoes_sem_nota: array<int, string>, jurados_incompletos: array<int, array{titulo: string, enviadas: int, total: int}>, empates: array<int, array{posicao: int, submissoes: array<int, string>}>}
     */
    public function handle(Event $event): array
    {
        $submissoes = Submission::forEvent($event)
            ->with(['assignments.evaluation', 'team'])
            ->get()
            ->filter(fn (Submission $s) => $s->status->countsForEvaluation());

        $resultsPorSubmissao = Result::forEvent($event)->get()->keyBy('submission_id');

        $semNota = [];
        $incompletos = [];

        foreach ($submissoes as $submissao) {
            $result = $resultsPorSubmissao->get($submissao->id);
            $titulo = $submissao->title ?? "submissão #{$submissao->id}";

            if (! $result || $result->final_score === null) {
                $semNota[] = $titulo;
            }

            $total = $submissao->assignments->count();
            $enviadas = $submissao->assignments
                ->filter(fn (JudgeAssignment $a) => $a->evaluation?->status === EvaluationStatus::Submitted)
                ->count();

            if ($total > 0 && $enviadas < $total) {
                $incompletos[] = ['titulo' => $titulo, 'enviadas' => $enviadas, 'total' => $total];
            }
        }

        $empates = Result::forEvent($event)
            ->whereNotNull('rank_overall')
            ->with('submission')
            ->get()
            ->groupBy('rank_overall')
            ->filter(fn ($grupo) => $grupo->count() > 1)
            ->map(fn ($grupo, $posicao) => [
                'posicao' => (int) $posicao,
                'submissoes' => $grupo->map(fn (Result $r) => $r->submission->title ?? "submissão #{$r->submission_id}")->all(),
            ])
            ->values()
            ->all();

        return [
            'submissoes_sem_nota' => $semNota,
            'jurados_incompletos' => $incompletos,
            'empates' => $empates,
        ];
    }
}
