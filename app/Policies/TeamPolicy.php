<?php

namespace App\Policies;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    /**
     * Criar equipe. Prazo é autorização, não um if no controller.
     */
    public function create(User $user, Event $event): Response
    {
        if (! $event->isRegistered($user)) {
            return Response::deny('Inscreva-se no evento antes de criar uma equipe.');
        }

        if (! $event->registrationIsOpen()) {
            return Response::deny('O prazo para formar equipes já encerrou.');
        }

        if ($this->alreadyInATeam($user, $event)) {
            return Response::deny('Você já faz parte de uma equipe neste evento.');
        }

        return Response::allow();
    }

    /**
     * Entrar em uma equipe existente pelo código de convite. A equipe já
     * chega resolvida -- o código em si é validado no Form Request, que
     * não distingue "não existe" de "é de outro evento" para não dar pista
     * de que um código alheio existe.
     */
    public function join(User $user, Team $team): Response
    {
        $event = $team->event;

        if (! $event->isRegistered($user)) {
            return Response::deny('Inscreva-se no evento antes de entrar em uma equipe.');
        }

        if (! $event->registrationIsOpen()) {
            return Response::deny('O prazo para entrar em uma equipe já encerrou.');
        }

        if ($this->alreadyInATeam($user, $event)) {
            return Response::deny('Você já faz parte de uma equipe neste evento.');
        }

        if ($team->isFull()) {
            return Response::deny('Esta equipe já atingiu o número máximo de integrantes.');
        }

        if ($team->status === TeamStatus::Disqualified) {
            return Response::deny('Esta equipe foi desclassificada e não pode receber novos integrantes.');
        }

        return Response::allow();
    }

    /**
     * Ver a equipe. Organizador enxerga qualquer uma; participante, só a sua.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->isStaff() || $team->hasMember($user);
    }

    /**
     * Editar dados da equipe: só o líder, e só enquanto o prazo permite.
     */
    public function update(User $user, Team $team): Response
    {
        if ($user->isStaff()) {
            return Response::allow();
        }

        if (! $team->isLeader($user)) {
            return Response::deny('Apenas o líder pode alterar os dados da equipe.');
        }

        if (! $team->event->registrationIsOpen()) {
            return Response::deny('O prazo para alterar a equipe já encerrou. Procure a organização.');
        }

        return Response::allow();
    }

    /**
     * Passar a liderança adiante. Só o líder atual, e só enquanto o prazo
     * de formação de equipes permite mexer na composição.
     */
    public function transferLeadership(User $user, Team $team): Response
    {
        if (! $team->isLeader($user)) {
            return Response::deny('Apenas o líder pode passar a liderança adiante.');
        }

        if (! $team->event->registrationIsOpen()) {
            return Response::deny('O prazo para mudar a equipe já encerrou. Procure a organização.');
        }

        if ($team->activeMemberships()->where('user_id', '!=', $user->id)->doesntExist()) {
            return Response::deny('Não há outro integrante para receber a liderança.');
        }

        return Response::allow();
    }

    private function alreadyInATeam(User $user, Event $event): bool
    {
        return TeamMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active)
            ->exists();
    }
}
