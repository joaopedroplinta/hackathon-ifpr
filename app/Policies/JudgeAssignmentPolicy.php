<?php

namespace App\Policies;

use App\Models\JudgeAssignment;
use App\Models\User;

/**
 * Gerenciar atribuição é só do organizador. O jurado enxerga a própria fila
 * por outro caminho -- Judge\EvaluationController escopa direto por
 * judge_id, sem passar por esta Policy (regras-avaliacao: "jurado só
 * enxerga o que foi atribuído a ele").
 */
class JudgeAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, JudgeAssignment $assignment): bool
    {
        return $user->isStaff();
    }
}
