<?php

namespace App\Policies;

use App\Models\User;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function recompute(User $user): bool
    {
        return $user->isStaff();
    }

    /** Publicação é ação manual e explícita do organizador (PLANO.md §7). */
    public function publish(User $user): bool
    {
        return $user->isStaff();
    }
}
