<?php

namespace App\Actions\Teams;

use App\Enums\TeamMemberStatus;
use App\Models\TeamMember;

class RemoveTeamMember
{
    /**
     * O líder tira alguém da equipe.
     *
     * Mesma marcação de saída: a pessoa fica com o histórico e volta a poder
     * entrar em outra equipe. A diferença é só quem tomou a decisão, e isso
     * já fica no activity log pela Policy que autorizou.
     */
    public function handle(TeamMember $membership): void
    {
        $membership->forceFill([
            'status' => TeamMemberStatus::Left,
            'left_at' => now(),
        ])->save();
    }
}
