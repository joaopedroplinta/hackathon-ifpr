<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Rede de segurança para dado legado ou externo à admin/usuarios: encontra
 * e corrige usuários que acumulam participante com um papel privilegiado
 * (jurado/organizador/admin), sem nunca tocar em inscrição, equipe,
 * submissão, avaliação ou certificado -- só a linha do papel em si.
 */
class StripIncompatibleParticipanteRole
{
    /**
     * @return Collection<int, User>
     */
    public function find(): Collection
    {
        return User::role(Role::Participante->value)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', Role::privileged()))
            ->get();
    }

    public function handle(User $user): void
    {
        $antes = $user->getRoleNames()->all();

        $user->removeRole(Role::Participante->value);

        activity()
            ->performedOn($user)
            ->withProperties(['antes' => $antes, 'depois' => $user->getRoleNames()->all()])
            ->log('Papel participante removido por incompatibilidade (comando de manutenção)');
    }
}
