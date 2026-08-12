<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    /** Lista/emissão avulsa no painel do organizador. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function issue(User $user): bool
    {
        return $user->isStaff();
    }

    /** Dono do certificado ou staff -- nunca certificado de outra pessoa. */
    public function view(User $user, Certificate $certificate): bool
    {
        return $certificate->user_id === $user->id || $user->isStaff();
    }

    public function download(User $user, Certificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }
}
