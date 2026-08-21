<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Gerenciar o papel de outro usuário é exclusivo de admin -- organizador
 * faz o CRUD operacional do evento, mas não decide quem mais vira staff
 * (PLANO.md §3: "admin: Tudo + gerenciar usuários e papéis").
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function updateRoles(User $user, User $target): bool
    {
        return $user->hasRole(Role::Admin->value);
    }
}
