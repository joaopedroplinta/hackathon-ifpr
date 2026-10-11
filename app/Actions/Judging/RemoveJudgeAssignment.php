<?php

namespace App\Actions\Judging;

use App\Models\JudgeAssignment;
use App\Models\User;

/**
 * Remove a atribuição e deixa o rastro no activity log. A Policy já nega
 * quando a avaliação foi enviada (nota é registro histórico): nesse caso o
 * caminho é reabrir com motivo, não apagar.
 */
class RemoveJudgeAssignment
{
    public function handle(JudgeAssignment $assignment, User $autor): void
    {
        activity()
            ->causedBy($autor)
            ->performedOn($assignment->submission)
            ->withProperties([
                'judge_id' => $assignment->judge_id,
                'avaliacao_em_rascunho' => $assignment->evaluation !== null,
            ])
            ->log('Atribuição de jurado removida');

        $assignment->delete();
    }
}
