<?php

namespace App\Policies;

use App\Enums\EvaluationStatus;
use App\Models\JudgeAssignment;
use App\Models\User;

/**
 * Avaliação não tem rota "/evaluations/{id}" -- o jurado sempre chega nela
 * pela própria submissão (Judge\EvaluationController), que já resolve a
 * atribuição escopada por judge_id antes de perguntar à Policy. Estes
 * métodos recebem a JudgeAssignment (não a Evaluation, que pode nem existir
 * ainda) via `authorize('view', [Evaluation::class, $assignment])`.
 */
class EvaluationPolicy
{
    public function view(User $user, JudgeAssignment $assignment): bool
    {
        return $assignment->judge_id === $user->id;
    }

    /** Avaliação enviada só volta a ser editável por ação do organizador -- não pelo autosave. */
    public function update(User $user, JudgeAssignment $assignment): bool
    {
        if ($assignment->judge_id !== $user->id) {
            return false;
        }

        return $assignment->evaluation?->status !== EvaluationStatus::Submitted;
    }
}
