<?php

namespace App\Actions\Notifications;

use App\Models\Event;
use App\Models\Team;
use App\Notifications\SubmissionDeadlineReminder;
use Illuminate\Support\Facades\Notification;

/**
 * Dispara sozinho, via `hackathon:send-deadline-reminders` agendado
 * (routes/console.php) -- "pronto quando" da semana 7 (PLANO.md). Cada
 * limiar (24h, 1h) só dispara uma vez por evento: `reminder_Xh_sent_at`
 * evita reenvio quando o comando roda de novo minutos depois.
 */
class SendDeadlineReminders
{
    /**
     * @return array{enviados_24h: int, enviados_1h: int}
     */
    public function handle(Event $event): array
    {
        $enviados = ['enviados_24h' => 0, 'enviados_1h' => 0];

        if ($event->submission_deadline === null) {
            return $enviados;
        }

        if ($this->deveDisparar($event, 'reminder_24h_sent_at', 24)) {
            $enviados['enviados_24h'] = $this->notificarEquipesPendentes($event, 24);
            $event->reminder_24h_sent_at = now();
            $event->save();
        }

        if ($this->deveDisparar($event, 'reminder_1h_sent_at', 1)) {
            $enviados['enviados_1h'] = $this->notificarEquipesPendentes($event, 1);
            $event->reminder_1h_sent_at = now();
            $event->save();
        }

        return $enviados;
    }

    private function deveDisparar(Event $event, string $coluna, int $horasAntes): bool
    {
        if ($event->{$coluna} !== null) {
            return false;
        }

        // effectiveSubmissionDeadline(), não a coluna crua: um incidente que
        // estende o prazo (Anexo A.3) empurra a janela do lembrete junto --
        // senão o "falta 1h" dispara cedo demais, ou nunca mais dispara pro
        // prazo de verdade porque a coluna de dedupe já foi marcada.
        $prazo = $event->effectiveSubmissionDeadline();

        if ($prazo === null) {
            return false;
        }

        $agora = now();

        return $agora->greaterThanOrEqualTo($prazo->clone()->subHours($horasAntes))
            && $agora->lessThan($prazo);
    }

    private function notificarEquipesPendentes(Event $event, int $horasRestantes): int
    {
        $equipes = Team::forEvent($event)
            ->with(['submission', 'activeMemberships.user', 'event'])
            ->get()
            ->filter(fn (Team $team) => ! $team->submission || $team->submission->isDraft());

        foreach ($equipes as $team) {
            $usuarios = $team->activeMemberships->pluck('user');
            Notification::send($usuarios, new SubmissionDeadlineReminder($team, $horasRestantes));
        }

        return $equipes->count();
    }
}
