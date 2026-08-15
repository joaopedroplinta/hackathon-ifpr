<?php

namespace App\Actions\Users;

use App\Models\User;

/**
 * "Excluir conta" precisa apagar dado pessoal de verdade (LGPD, direito de
 * eliminação), mas certificado, avaliação e liderança de equipe são
 * registro histórico que não pode sumir (.claude/rules/database.md) --
 * por isso anonimiza em vez de excluir a linha inteira. O soft delete
 * (App\Models\User) tira a conta de circulação: escopo global do Eloquent
 * já esconde de toda consulta normal, e login para de funcionar sozinho.
 */
class AnonymizeUser
{
    public function handle(User $user): void
    {
        $user->registrations()->update([
            'phone' => null,
            'course' => null,
            'dietary_notes' => null,
        ]);

        $user->forceFill([
            'name' => 'Usuário removido',
            'email' => "removido-{$user->id}@removido.local",
            'google_id' => null,
            'avatar_url' => null,
        ])->save();

        $user->delete();
    }
}
