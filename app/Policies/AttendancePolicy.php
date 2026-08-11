<?php

namespace App\Policies;

use App\Models\User;

/**
 * Controle fica sempre com a organização -- participante nunca confirma a
 * própria presença, mesmo escaneando o próprio QR (PLANO.md, seção 4).
 */
class AttendancePolicy
{
    public function create(User $user): bool
    {
        return $user->isStaff();
    }
}
