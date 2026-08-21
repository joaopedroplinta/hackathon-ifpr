<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Fica no activity log com autor e horário: conceder ou remover papel --
 * principalmente admin -- não pode ser silencioso
 * (.claude/rules/security.md, "Auditoria").
 */
class UpdateUserRoles
{
    /**
     * @param  array<int, string>  $roles
     */
    public function handle(User $ator, User $alvo, array $roles): void
    {
        if ($ator->is($alvo) && ! in_array(Role::Admin->value, $roles, true)) {
            throw ValidationException::withMessages([
                'roles' => 'Você não pode remover seu próprio papel de administrador.',
            ]);
        }

        $antes = $alvo->getRoleNames()->all();

        $alvo->syncRoles($roles);

        activity()
            ->causedBy($ator)
            ->performedOn($alvo)
            ->withProperties(['antes' => $antes, 'depois' => $roles])
            ->log('Papéis atualizados');
    }
}
