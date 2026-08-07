<?php

namespace App\Policies;

use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Str;

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

    private function alreadyInATeam(User $user, Event $event): bool
    {
        return TeamMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active)
            ->exists();
    }

    /**
     * Convidar alguém por e-mail. Só o líder convida, só enquanto o prazo
     * de formação de equipes permitir, e nunca pra quem já está na equipe
     * ou já tem outra equipe ativa neste evento.
     *
     * $email fica opcional: sem ele, a checagem serve só pra decidir se o
     * formulário de convite aparece na tela (equipe cheia, prazo etc.) --
     * as regras específicas de quem está sendo convidado só entram quando
     * o e-mail é conhecido, no momento do envio de verdade.
     */
    public function invite(User $user, Team $team, ?string $email = null): Response
    {
        if (! $team->isLeader($user)) {
            return Response::deny('Apenas o líder pode convidar novos integrantes.');
        }

        if (! $team->event->registrationIsOpen()) {
            return Response::deny('O prazo para formar equipes já encerrou.');
        }

        if ($team->isFull()) {
            return Response::deny('A equipe já atingiu o número máximo de integrantes.');
        }

        if ($email === null) {
            return Response::allow();
        }

        $email = Str::lower($email);

        if ($team->activeMemberships()->whereHas('user', fn ($q) => $q->where('email', $email))->exists()) {
            return Response::deny('Essa pessoa já faz parte da equipe.');
        }

        if ($this->hasActiveTeamInEvent($email, $team->event)) {
            return Response::deny('Essa pessoa já tem uma equipe ativa neste evento.');
        }

        return Response::allow();
    }

    private function hasActiveTeamInEvent(string $email, Event $event): bool
    {
        return TeamMember::query()
            ->where('event_id', $event->id)
            ->where('status', TeamMemberStatus::Active)
            ->whereHas('user', fn ($q) => $q->where('email', $email))
            ->exists();
    }
}
