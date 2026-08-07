<?php

namespace App\Actions\Teams;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JoinTeamByCode
{
    /**
     * Resolve a equipe pelo código de convite e grava a participação ativa.
     *
     * As duas coisas na mesma transação: resolver a equipe e criar o membro
     * separados deixaria uma janela em que a equipe lotou ou foi apagada
     * entre uma coisa e outra. O índice parcial do Postgres em
     * team_members ainda segura a corrida de duplo clique por cima disto.
     *
     * @return Team|null Null quando o código não resolve para uma equipe
     *                   deste evento -- quem chama decide como reagir.
     */
    public function handle(Event $event, User $user, string $inviteCode): ?Team
    {
        return DB::transaction(function () use ($event, $user, $inviteCode) {
            $team = Team::forEvent($event)->withInviteCode($inviteCode)->first();

            if (! $team) {
                return null;
            }

            $membership = new TeamMember;
            $membership->event_id = $event->id;
            $membership->team_id = $team->id;
            $membership->user_id = $user->id;
            $membership->role = TeamMemberRole::Member;
            $membership->status = TeamMemberStatus::Active;
            $membership->joined_at = now();
            $membership->save();

            return $team;
        });
    }
}
