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

        $temParticipante = in_array(Role::Participante->value, $roles, true);
        $temPrivilegiado = array_intersect($roles, Role::privileged()) !== [];
        $participanteRemovidoAutomaticamente = false;

        if ($temParticipante && $temPrivilegiado) {
            if (in_array(Role::Participante->value, $antes, true)) {
                // Promoção: quem já era participante virou jurado/organizador/
                // admin. Remove participante sem exigir que o admin desmarque
                // manualmente antes de promover.
                $roles = array_values(array_diff($roles, [Role::Participante->value]));
                $participanteRemovidoAutomaticamente = true;
            } else {
                // Tentativa de ADICIONAR participante a quem já tem (ou vai
                // ganhar) papel privilegiado -- recusa a operação inteira, não
                // remove o papel privilegiado pra "resolver".
                throw ValidationException::withMessages([
                    'roles' => 'Participante não pode acumular com jurado, organizador ou admin. Desmarque um dos dois antes de salvar.',
                ]);
            }
        }

        $alvo->syncRoles($roles);

        activity()
            ->causedBy($ator)
            ->performedOn($alvo)
            ->withProperties([
                'antes' => $antes,
                'depois' => $roles,
                ...($participanteRemovidoAutomaticamente ? ['participante_removido_automaticamente' => true] : []),
            ])
            ->log('Papéis atualizados');
    }
}
