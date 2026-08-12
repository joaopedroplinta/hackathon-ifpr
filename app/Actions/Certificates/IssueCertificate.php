<?php

namespace App\Actions\Certificates;

use App\Enums\CertificateType;
use App\Jobs\GenerateCertificatePdf;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Support\AttendanceHours;
use Illuminate\Support\Str;

/**
 * Emite (ou reemite) um certificado e enfileira a geração do PDF. Único
 * ponto de escrita em `certificates` -- tanto a emissão automática em lote
 * quanto a emissão avulsa manual passam por aqui, então idempotência
 * (um certificado por tipo por pessoa por evento) vale pras duas.
 */
class IssueCertificate
{
    public function __construct(private AttendanceHours $attendanceHours)
    {
        //
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Event $event, User $user, CertificateType $type, array $payload = []): Certificate
    {
        if ($type->usesAttendanceHours()) {
            $payload['carga_horaria'] = $this->attendanceHours->forUser($event, $user);
        }

        $certificate = Certificate::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('type', $type->value)
            ->first() ?? new Certificate;

        $certificate->event_id = $event->id;
        $certificate->user_id = $user->id;
        $certificate->type = $type;
        $certificate->code = $certificate->code ?? (string) Str::uuid();
        $certificate->payload = $payload;
        $certificate->path = null;
        $certificate->issued_at = now();
        $certificate->save();

        GenerateCertificatePdf::dispatch($certificate->id);

        return $certificate;
    }
}
