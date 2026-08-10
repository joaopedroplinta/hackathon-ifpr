<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * "Qual evento?" e "qual equipe?" são a primeira pergunta de toda tela de
 * participante. Ficam aqui para as duas respostas serem sempre a mesma --
 * dois controllers resolvendo o evento atual de jeitos diferentes é como
 * um lado do sistema enxerga a edição passada.
 */
trait ResolvesParticipation
{
    protected function currentEventOrFail(): Event
    {
        return Event::current() ?? throw new NotFoundHttpException('Nenhum evento publicado no momento.');
    }

    /** A equipe ativa do usuário neste evento, se houver. */
    protected function teamOf(User $user, Event $event): ?Team
    {
        return Team::query()
            ->forEvent($event)
            ->whereHas(
                'memberships',
                fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', TeamMemberStatus::Active)
            )
            ->first();
    }
}
