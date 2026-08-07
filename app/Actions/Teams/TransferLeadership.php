<?php

namespace App\Actions\Teams;

use App\Enums\TeamMemberRole;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Support\Facades\DB;

class TransferLeadership
{
    /**
     * Passa a liderança para outro integrante ativo.
     *
     * Três escritas que precisam acontecer juntas: o novo líder vira Leader,
     * o antigo volta a Member e a equipe passa a apontar para o novo. Metade
     * disso gravado deixaria a equipe com dois líderes ou nenhum.
     */
    public function handle(Team $team, TeamMember $novoLider): void
    {
        DB::transaction(function () use ($team, $novoLider) {
            $antigo = $team->activeMemberships()
                ->where('user_id', $team->leader_id)
                ->first();

            $antigo?->forceFill(['role' => TeamMemberRole::Member])->save();

            $novoLider->forceFill(['role' => TeamMemberRole::Leader])->save();

            $team->forceFill(['leader_id' => $novoLider->user_id])->save();
        });
    }
}
