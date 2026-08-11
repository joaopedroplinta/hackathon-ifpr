<?php

namespace App\Policies;

use App\Models\User;

class ConflictOfInterestPolicy
{
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user): bool
    {
        return $user->isStaff();
    }
}
