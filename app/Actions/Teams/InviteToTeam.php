<?php

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use App\Notifications\TeamInviteNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InviteToTeam
{
    /**
     * Cria o convite e dispara o e-mail em fila.
     *
     * O e-mail é enviado depois que a transação fecha: o worker da fila
     * roda em outro processo e não pode enxergar uma linha que ainda não
     * foi commitada.
     */
    public function handle(Team $team, User $inviter, string $email): TeamInvite
    {
        $email = Str::lower($email);

        $invite = DB::transaction(function () use ($team, $inviter, $email) {
            $invite = new TeamInvite;
            $invite->event_id = $team->event_id;
            $invite->team_id = $team->id;
            $invite->email = $email;
            // Nunca derivado do id ou do e-mail -- .claude/rules/security.md.
            $invite->token = Str::random(40);
            $invite->invited_by = $inviter->id;
            $invite->expires_at = $this->expiresAt($team);
            $invite->save();

            return $invite;
        });

        Notification::route('mail', $email)->notify(new TeamInviteNotification($invite));

        return $invite;
    }

    /**
     * Padrão de 7 dias, mas nunca depois do prazo de formação de equipes:
     * um convite que valesse além disso seria uma porta lateral pra entrar
     * na equipe fora do prazo -- PLANO.md seção 4.
     */
    private function expiresAt(Team $team): Carbon
    {
        $default = now()->addDays(7);
        $closesAt = $team->event->registration_closes_at;

        if ($closesAt !== null && $default->gt($closesAt)) {
            return $closesAt;
        }

        return $default;
    }
}
