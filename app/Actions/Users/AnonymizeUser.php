<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Support\AvatarStorage;

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

        // CPF e matrícula identificam a pessoa tanto quanto nome/e-mail --
        // "anonimizado" que deixa CPF intacto não anonimizou nada de
        // verdade. Foto local some do disco também, não só da coluna:
        // manter o arquivo depois da conta anonimizada deixaria a imagem
        // do rosto da pessoa órfã em storage/app/public pra sempre.
        AvatarStorage::apagarSeLocal($user->avatar_url);

        $user->forceFill([
            'name' => 'Usuário removido',
            'email' => "removido-{$user->id}@removido.local",
            'google_id' => null,
            'avatar_url' => null,
            'cpf' => null,
            'tipo_vinculo' => null,
            'matricula_suap' => null,
            'matricula_siape' => null,
        ])->save();

        $user->delete();
    }
}
