<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

/**
 * Só usuário autenticado e inscrito no evento vota, e só dentro da janela
 * de votação (PLANO.md, seção 4). `authorize('create', [PopularVote::class,
 * $event])` -- o evento chega como argumento extra, ver Participant\PopularVoteController.
 */
class PopularVotePolicy
{
    public function create(User $user, Event $event): bool
    {
        return $event->isRegistered($user) && $event->votingIsOpen();
    }
}
