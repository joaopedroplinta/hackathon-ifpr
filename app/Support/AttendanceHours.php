<?php

namespace App\Support;

use App\Models\Event;
use App\Models\User;

/**
 * Carga horária do certificado vem das presenças registradas, nunca de um
 * valor fixo -- PLANO.md, seção "Certificados". Soma a duração de cada
 * checkpoint em que a pessoa tem presença confirmada.
 */
class AttendanceHours
{
    public function forUser(Event $event, User $user): float
    {
        $minutos = $user->attendances()
            ->whereHas('checkpoint', fn ($q) => $q->where('event_id', $event->id))
            ->with('checkpoint')
            ->get()
            ->sum(fn ($attendance) => $attendance->checkpoint->starts_at->diffInMinutes($attendance->checkpoint->ends_at));

        return round($minutos / 60, 1);
    }
}
