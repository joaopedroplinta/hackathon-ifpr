<?php

namespace App\Policies;

use App\Models\User;

class CheckpointPolicy
{
    public function create(User $user): bool
    {
        return $user->isStaff();
    }
}
