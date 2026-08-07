<?php

namespace App\Actions\Teams;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JoinTeamByCode
{
    /**
     * Resolve a equipe pelo código de convite e grava a participação ativa.
     *
     * @return Team|null Null quando o código não resolve para uma equipe
     *                   deste evento -- quem chama decide como reagir.
     */
    public function handle(Event $event, User $user, string $inviteCode): ?Team
    {
        return DB::transaction(function () use ($event, $user, $inviteCode) {
            // lockForUpdate segura a linha da equipe até o fim da transação.
            //
            // Sem isso existe uma corrida real: a Policy checa isFull() e a
            // gravação acontece depois. Duas pessoas usando o mesmo código
            // com uma vaga sobrando passam as duas, e a equipe estoura o
            // máximo. O índice parcial de team_members não pega este caso --
            // ele impede a MESMA pessoa em duas equipes, não duas pessoas
            // na mesma vaga.
            $team = Team::forEvent($event)
                ->withInviteCode($inviteCode)
                ->lockForUpdate()
                ->first();

            if (! $team) {
                return null;
            }

            // Recontagem já sob o lock: é este número que decide, não o que
            // a Policy viu alguns milissegundos atrás.
            if ($team->isFull()) {
                throw ValidationException::withMessages([
                    'invite_code' => 'Esta equipe acabou de lotar. Peça o código de outra.',
                ]);
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
