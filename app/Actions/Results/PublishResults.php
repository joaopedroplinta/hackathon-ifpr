<?php

namespace App\Actions\Results;

use App\Models\Event;
use App\Models\User;
use App\Notifications\ResultsPublished;
use Illuminate\Support\Facades\Notification;

/**
 * Publicação é ação manual e explícita do organizador (PLANO.md §7).
 * Notifica só na transição -- reclicar "publicar" depois de recalcular não
 * manda o e-mail nos inscritos de novo.
 *
 * Fica no activity log com autor e horário -- é a ação mais sensível do
 * sistema, decide o prêmio (.claude/rules/security.md, "Auditoria").
 */
class PublishResults
{
    public function handle(Event $event, User $autor, bool $comPendencia = false): void
    {
        $jaEstavaPublicado = $event->results_published_at !== null;

        $event->results_published_at = now();
        $event->save();

        activity()
            ->causedBy($autor)
            ->performedOn($event)
            ->withProperties(['com_pendencia' => $comPendencia])
            ->log('Resultado publicado');

        if ($jaEstavaPublicado) {
            return;
        }

        $inscritos = User::query()
            ->whereHas('registrations', fn ($q) => $q->where('event_id', $event->id))
            ->get();

        Notification::send($inscritos, new ResultsPublished($event));
    }
}
