<?php

namespace App\Actions\Evaluation;

use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Models\JudgeAssignment;
use App\Models\User;

/**
 * Reabre uma avaliação enviada pra correção. A JudgeAssignmentPolicy já
 * garante que só uma avaliação com status submitted chega aqui -- esta
 * Action confia nisso e não repete a checagem.
 *
 * Fica no activity log com autor, horário e motivo: alterar nota já
 * submetida não pode ser silencioso (.claude/rules/security.md, "Auditoria").
 */
class ReopenEvaluation
{
    public function handle(JudgeAssignment $assignment, string $reason, User $autor): void
    {
        $evaluation = $assignment->evaluation;

        $evaluation->status = EvaluationStatus::Draft;
        $evaluation->submitted_at = null;
        $evaluation->save();

        $assignment->status = JudgeAssignmentStatus::InProgress;
        $assignment->save();

        activity()
            ->causedBy($autor)
            ->performedOn($evaluation)
            ->withProperties([
                'motivo' => $reason,
                'jurado_id' => $assignment->judge_id,
                'submission_id' => $assignment->submission_id,
            ])
            ->log('Avaliação reaberta para correção');
    }
}
