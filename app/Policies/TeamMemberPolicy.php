<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamMemberPolicy
{
    /**
     * Sair da própria equipe.
     *
     * O caso perigoso é o líder: se ele sai e sobra gente, a equipe fica
     * órfã — sem ninguém autorizado a editar, convidar ou submeter. Por isso
     * a saída é recusada até que a liderança seja passada adiante.
     *
     * Líder sozinho é outro caso: não há para quem transferir, e a saída
     * dissolve a equipe. Isso é permitido.
     */
    public function leave(User $user, TeamMember $membership): Response
    {
        if ($membership->user_id !== $user->id) {
            return Response::deny('Você só pode sair da sua própria equipe.');
        }

        if (! $membership->isActive()) {
            return Response::deny('Você já não faz parte desta equipe.');
        }

        $team = $membership->team;

        if (! $team->event->registrationIsOpen()) {
            return Response::deny('O prazo para mudar de equipe já encerrou. Procure a organização.');
        }

        if ($team->isLeader($user) && $this->hasOtherActiveMembers($membership)) {
            return Response::deny('Passe a liderança para outro integrante antes de sair.');
        }

        return Response::allow();
    }

    /**
     * O líder tira alguém da equipe. Não serve para o líder tirar a si
     * mesmo: para isso existe transferir a liderança ou sair.
     */
    public function remove(User $user, TeamMember $membership): Response
    {
        $team = $membership->team;

        if (! $team->isLeader($user)) {
            return Response::deny('Apenas o líder pode remover integrantes.');
        }

        if ($membership->user_id === $user->id) {
            return Response::deny('Para sair da equipe, passe a liderança adiante e use "Sair da equipe".');
        }

        if (! $membership->isActive()) {
            return Response::deny('Esta pessoa já não faz parte da equipe.');
        }

        if (! $team->event->registrationIsOpen()) {
            return Response::deny('O prazo para mudar a equipe já encerrou. Procure a organização.');
        }

        return Response::allow();
    }

    private function hasOtherActiveMembers(TeamMember $membership): bool
    {
        return $membership->team
            ->activeMemberships()
            ->where('user_id', '!=', $membership->user_id)
            ->exists();
    }
}
