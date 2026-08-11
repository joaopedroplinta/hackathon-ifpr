<?php

namespace App\Actions\Evaluation;

use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;

/**
 * Autosave: jurado avalia pelo celular, em pé, com wi-fi de evento --
 * perder nota digitada é falha grave (regras-avaliacao). Aceita nota
 * parcial -- rascunho não precisa cobrir toda a rubrica, só o envio exige.
 */
class SaveEvaluationDraft
{
    /**
     * @param  array<int, array{criterion_id: int, score: float|null, comment: string|null}>  $scores
     */
    public function handle(JudgeAssignment $assignment, array $scores, ?string $overallComment): Evaluation
    {
        $evaluation = $assignment->evaluation;

        if (! $evaluation) {
            $evaluation = new Evaluation;
            $evaluation->assignment_id = $assignment->id;
            $evaluation->status = EvaluationStatus::Draft;
            $evaluation->save();
        }

        $evaluation->overall_comment = $overallComment;
        $evaluation->save();

        foreach ($scores as $linha) {
            $criterioScore = EvaluationScore::query()
                ->where('evaluation_id', $evaluation->id)
                ->where('criterion_id', $linha['criterion_id'])
                ->first();

            if (! $criterioScore) {
                $criterioScore = new EvaluationScore;
                $criterioScore->evaluation_id = $evaluation->id;
                $criterioScore->criterion_id = $linha['criterion_id'];
            }

            $criterioScore->score = $linha['score'];
            $criterioScore->comment = $linha['comment'];
            $criterioScore->save();
        }

        if ($assignment->status === JudgeAssignmentStatus::Pending) {
            $assignment->status = JudgeAssignmentStatus::InProgress;
            $assignment->save();
        }

        return $evaluation->fresh('scores');
    }
}
