<?php

namespace App\Actions\Submissions;

use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\User;

/**
 * O organizador decide o que fazer com um envio fora do prazo (PLANO.md,
 * Anexo A): aceitar faz a submissão concorrer, recusar a deixa de fora.
 * Decisão fica no activity log com autor e motivo -- é ela que decide quem
 * disputa o prêmio (.claude/rules/security.md, "Auditoria"). Pode ser
 * revista: o log guarda o estado anterior.
 */
class DecideLateSubmission
{
    public function handle(Submission $submission, bool $aceitar, string $motivo, User $autor): void
    {
        $de = $submission->status;

        $submission->status = $aceitar ? SubmissionStatus::LateAccepted : SubmissionStatus::LateRejected;
        $submission->save();

        activity()
            ->causedBy($autor)
            ->performedOn($submission)
            ->withProperties(['de' => $de->value, 'para' => $submission->status->value, 'motivo' => $motivo])
            ->log($aceitar ? 'Envio fora do prazo aceito' : 'Envio fora do prazo recusado');
    }
}
