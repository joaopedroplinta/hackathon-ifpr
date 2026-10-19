<?php

namespace App\Actions\Evaluation;

use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;

/**
 * Envio explícito -- só a partir daqui a avaliação conta pra nota final
 * (regras-avaliacao: rascunho nunca conta). O Form Request já garantiu que
 * toda nota da rubrica ativa veio preenchida antes de chegar aqui.
 */
class SubmitEvaluation
{
    /**
     * @param  array<int, array{criterion_id: int, score: float|null, comment: string|null}>  $scores
     */
    public function handle(JudgeAssignment $assignment, array $scores, ?string $overallComment): Evaluation
    {
        $evaluation = app(SaveEvaluationDraft::class)->handle($assignment, $scores, $overallComment);

        // Rascunho de uma rubrica anterior (ou nota em branco) sobrevive ao
        // upsert e entraria na média ponderada: o envio vale só para o que foi
        // pontuado agora, contra a rubrica ativa.
        $evaluation->scores()
            ->where(fn ($q) => $q
                ->whereNotIn('criterion_id', collect($scores)->pluck('criterion_id'))
                ->orWhereNull('score'))
            ->delete();

        $evaluation->status = EvaluationStatus::Submitted;
        $evaluation->submitted_at = now();
        $evaluation->save();

        $assignment->status = JudgeAssignmentStatus::Done;
        $assignment->save();

        return $evaluation->fresh('scores');
    }
}
