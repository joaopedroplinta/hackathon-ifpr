<?php

namespace App\Policies;

use App\Models\ScheduleItem;
use App\Models\User;

/**
 * Só o organizador mexe na agenda. Participante e jurado só leem, pela tela
 * pública -- ver Public\AgendaController, que nem passa por Policy porque
 * é público por natureza.
 */
class ScheduleItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, ScheduleItem $item): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, ScheduleItem $item): bool
    {
        return $user->isStaff();
    }
}
