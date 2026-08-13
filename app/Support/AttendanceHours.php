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
        // starts_at/ends_at são nullable -- o formulário rápido de criar
        // checkpoint (admin/checkin) só pede nome e tipo, então checkpoint
        // sem horário é o caso comum, não a exceção. Sem duração conhecida,
        // a presença conta pro certificado mas não soma carga horária.
        $minutos = $user->attendances()
            ->whereHas('checkpoint', fn ($q) => $q->where('event_id', $event->id))
            ->with('checkpoint')
            ->get()
            ->filter(fn ($attendance) => $attendance->checkpoint->starts_at !== null && $attendance->checkpoint->ends_at !== null)
            ->sum(fn ($attendance) => $attendance->checkpoint->starts_at->diffInMinutes($attendance->checkpoint->ends_at));

        return round($minutos / 60, 1);
    }
}
