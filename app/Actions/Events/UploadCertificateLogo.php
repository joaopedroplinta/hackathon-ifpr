<?php

namespace App\Actions\Events;

use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UploadCertificateLogo
{
    /**
     * Substitui o logo do certificado. O nome no disco é gerado pelo
     * Laravel, e o caminho antigo nunca é reaproveitado -- um certificado já
     * emitido guarda o caminho antigo no `payload` (snapshot em
     * IssueCertificate) e precisa continuar achando o arquivo antigo depois
     * da troca.
     */
    public function handle(Event $event, UploadedFile $upload): Event
    {
        return DB::transaction(function () use ($event, $upload) {
            $event->certificate_logo_path = $upload->store('certificados/logos', 'local');
            $event->save();

            return $event;
        });
    }
}
