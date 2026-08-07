<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EventPolicy
{
    /**
     * Inscrever-se no evento.
     *
     * A regra de prazo mora aqui, não num if do controller: prazo é
     * autorização, e a Policy é o único lugar que a interface e o servidor
     * consultam. Response::deny carrega o motivo, então o usuário lê por que
     * não pode em vez de um 403 mudo.
     */
    public function register(User $user, Event $event): Response
    {
        if (! $user->hasVerifiedEmail()) {
            return Response::deny('Confirme seu e-mail antes de se inscrever no evento.');
        }

        if (! $event->registrationIsOpen()) {
            return Response::deny('As inscrições para este evento não estão abertas.');
        }

        if ($event->isRegistered($user)) {
            return Response::deny('Você já está inscrito neste evento.');
        }

        return Response::allow();
    }
}
