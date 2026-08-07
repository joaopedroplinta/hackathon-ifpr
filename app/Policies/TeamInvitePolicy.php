<?php

namespace App\Policies;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Str;

class TeamInvitePolicy
{
    /**
     * Aceitar convite. Cada recusa tem mensagem própria -- convite
     * expirado, já aceito e e-mail trocado são erro do usuário, não bug, e
     * merecem texto que explique o que aconteceu em vez de um 403 mudo.
     *
     * A checagem de equipe ativa roda ANTES de qualquer escrita: o índice
     * parcial do Postgres em team_members só pega a corrida de duplo
     * clique, não substitui esta validação -- .claude/rules/database.md.
     */
    public function accept(User $user, TeamInvite $invite): Response
    {
        if ($invite->isAccepted()) {
            return Response::deny('Este convite já foi aceito.');
        }

        if ($invite->isExpired()) {
            return Response::deny('Este convite expirou. Peça um novo convite ao líder da equipe.');
        }

        if (Str::lower($invite->email) !== Str::lower($user->email)) {
            return Response::deny('Este convite foi enviado para outro e-mail.');
        }

        if ($this->hasActiveTeamInEvent($user, $invite->event_id)) {
            return Response::deny('Você já faz parte de uma equipe neste evento.');
        }

        // Lotação é checada no envio do convite, mas vários convites podem
        // ficar pendentes para a mesma vaga: com 1 lugar livre o líder
        // consegue mandar três, porque nenhum foi aceito ainda. Sem esta
        // recusa, os três entram e a equipe estoura o máximo.
        if ($invite->team->isFull()) {
            return Response::deny('Esta equipe já atingiu o número máximo de integrantes.');
        }

        if ($invite->team->status === TeamStatus::Disqualified) {
            return Response::deny('Esta equipe foi desclassificada e não pode receber novos integrantes.');
        }

        return Response::allow();
    }

    private function hasActiveTeamInEvent(User $user, int $eventId): bool
    {
        return TeamMember::query()
            ->where('event_id', $eventId)
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active)
            ->exists();
    }
}
