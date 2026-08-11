<?php

namespace App\Actions\Checkins;

use App\Enums\AttendanceMethod;
use App\Models\Attendance;
use App\Models\Checkpoint;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class RegisterAttendance
{
    /**
     * Idempotente por natureza: reler o mesmo crachá no mesmo checkpoint
     * não duplica -- é o índice único (checkpoint_id, user_id) que garante
     * isso mesmo sob corrida. Atribuição direta, não mass assignment:
     * Attendance::$fillable é vazio de propósito, é o servidor que escreve,
     * nunca um formulário -- .claude/rules/security.md.
     *
     * @return array{0: Attendance, 1: bool} A presença e se foi criada agora.
     */
    public function handle(Checkpoint $checkpoint, User $participant, User $staff, AttendanceMethod $method): array
    {
        $existente = Attendance::query()
            ->where('checkpoint_id', $checkpoint->id)
            ->where('user_id', $participant->id)
            ->first();

        if ($existente) {
            return [$existente, false];
        }

        $attendance = new Attendance;
        $attendance->checkpoint_id = $checkpoint->id;
        $attendance->user_id = $participant->id;
        $attendance->checked_in_at = now();
        $attendance->checked_by = $staff->id;
        $attendance->method = $method;

        try {
            $attendance->save();
        } catch (UniqueConstraintViolationException) {
            // Duas leituras do mesmo crachá quase juntas: a outra ganhou a
            // corrida. O índice único garante que não duplicou -- só falta
            // devolver o registro que ela criou.
            return [
                Attendance::query()
                    ->where('checkpoint_id', $checkpoint->id)
                    ->where('user_id', $participant->id)
                    ->firstOrFail(),
                false,
            ];
        }

        return [$attendance, true];
    }
}
