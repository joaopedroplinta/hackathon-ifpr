<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Support\AvatarStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $avatarUrl = $user->avatar_url;

        // Tudo ou nada: sem transação uma falha no meio deixava a conta com
        // os dados pessoais intactos e as inscrições já limpas.
        DB::transaction(function () use ($user) {
            $user->registrations()->update([
                'phone' => null,
                'course' => null,
                'dietary_notes' => null,
            ]);

            // CPF e matrícula identificam a pessoa tanto quanto nome/e-mail --
            // "anonimizado" que deixa CPF intacto não anonimizou nada de
            // verdade. O e-mail usa UUID: com o id (sequencial) qualquer um
            // podia registrar o endereço antes e travar a exclusão pela
            // unique de users.email.
            $user->forceFill([
                'name' => 'Usuário removido',
                'email' => 'removido-'.Str::uuid().'@removido.invalid',
                'google_id' => null,
                'avatar_url' => null,
                'cpf' => null,
                'tipo_vinculo' => null,
                'matricula_suap' => null,
                'matricula_siape' => null,
            ])->save();

            $user->delete();
        });

        // Foto local some do disco também, não só da coluna: manter o
        // arquivo depois da conta anonimizada deixaria a imagem do rosto da
        // pessoa órfã em storage/app/public pra sempre. Fora da transação
        // porque apagar arquivo não dá rollback.
        AvatarStorage::apagarSeLocal($avatarUrl);
    }
}
