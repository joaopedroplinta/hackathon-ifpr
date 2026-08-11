<?php

namespace App\Policies;

use App\Models\Rubric;
use App\Models\User;

/**
 * Ver a rubrica é público (PLANO.md, Anexo A: rubrica pública desde o
 * início reduz disputa sobre nota) -- ver Public\RubricController, sem
 * Policy nenhuma, mesmo padrão de AgendaController. Só escrever passa
 * por aqui.
 */
class RubricPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Rubric $rubric): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Rubric $rubric): bool
    {
        return $user->isStaff();
    }
}
