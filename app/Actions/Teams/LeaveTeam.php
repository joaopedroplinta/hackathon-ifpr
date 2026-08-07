<?php

namespace App\Actions\Teams;

use App\Enums\TeamMemberStatus;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\DB;

class LeaveTeam
{
    /**
     * Marca a participação como encerrada em vez de apagá-la.
     *
     * O histórico importa: saber que alguém esteve na equipe e saiu é
     * diferente de nunca ter estado, e o organizador precisa dessa
     * distinção quando alguém reclama no dia. Além disso, o índice parcial
     * do Postgres só considera status = 'active', então marcar como 'left'
     * já libera a pessoa para entrar em outra equipe.
     *
     * @return bool Se a equipe foi dissolvida por ter ficado sem ninguém.
     */
    public function handle(TeamMember $membership): bool
    {
        return DB::transaction(function () use ($membership) {
            $team = $membership->team()->lockForUpdate()->firstOrFail();

            $membership->forceFill([
                'status' => TeamMemberStatus::Left,
                'left_at' => now(),
            ])->save();

            // Último a sair apaga a luz. Sem isso ficaria uma equipe sem
            // ninguém ocupando o nome e o código de convite.
            if ($this->isEmpty($team)) {
                $team->delete();

                return true;
            }

            return false;
        });
    }

    private function isEmpty(Team $team): bool
    {
        return ! $team->activeMemberships()->exists();
    }
}
