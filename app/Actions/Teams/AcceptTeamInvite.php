<?php

namespace App\Actions\Teams;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptTeamInvite
{
    /**
     * Grava a participação e marca o convite como aceito na mesma
     * transação: convite aceito sem membership é um estado que nenhuma
     * tela sabe exibir (mesmo raciocínio do CreateTeam).
     *
     * Quem chama esta Action já passou pela TeamInvitePolicy::accept, que
     * recusou convite expirado, já aceito, e-mail trocado e quem já tem
     * equipe ativa neste evento -- ANTES desta escrita, não depois. O
     * índice parcial do Postgres em team_members só pega a corrida de
     * duplo clique, não substitui aquela checagem.
     */
    public function handle(TeamInvite $invite, User $user): TeamMember
    {
        return DB::transaction(function () use ($invite, $user) {
            // Recontagem sob lock, mesmo padrao de JoinTeamByCode: a Policy
            // roda antes da escrita, entao dois convites aceitos ao mesmo
            // tempo para a ultima vaga passariam os dois por ela.
            $team = Team::whereKey($invite->team_id)->lockForUpdate()->firstOrFail();

            if ($team->isFull()) {
                throw ValidationException::withMessages([
                    'convite' => 'Esta equipe acabou de lotar. Fale com o líder.',
                ]);
            }

            $membership = new TeamMember;
            $membership->event_id = $invite->event_id;
            $membership->team_id = $invite->team_id;
            $membership->user_id = $user->id;
            $membership->role = TeamMemberRole::Member;
            $membership->status = TeamMemberStatus::Active;
            $membership->joined_at = now();
            $membership->save();

            $invite->accepted_at = now();
            $invite->save();

            return $membership;
        });
    }
}
