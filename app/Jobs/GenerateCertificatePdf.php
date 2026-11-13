<?php

namespace App\Jobs;

use App\Actions\Certificates\RenderCertificatePdf;
use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * PDF sempre em fila -- .claude/rules e PLANO.md são explícitos: nada
 * bloqueia o request nem o comando de emissão em lote esperando o dompdf.
 * Exceção: o download renderiza de novo quando o arquivo não está no disco
 * do web (worker e web não compartilham disco em produção, issue #222).
 */
class GenerateCertificatePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $certificateId)
    {
        //
    }

    public function handle(RenderCertificatePdf $render): void
    {
        $certificate = Certificate::find($this->certificateId);

        if (! $certificate) {
            return;
        }

        $certificate->path = $render->handle($certificate);
        $certificate->save();
    }
}
