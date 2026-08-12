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
 */
class PublishResults
{
    public function handle(Event $event): void
    {
        $jaEstavaPublicado = $event->results_published_at !== null;

        $event->results_published_at = now();
        $event->save();

        if ($jaEstavaPublicado) {
            return;
        }

        $inscritos = User::query()
            ->whereHas('registrations', fn ($q) => $q->where('event_id', $event->id))
            ->get();

        Notification::send($inscritos, new ResultsPublished($event));
    }
}
