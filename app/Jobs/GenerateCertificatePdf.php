<?php

namespace App\Jobs;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * PDF sempre em fila -- .claude/rules e PLANO.md são explícitos: nada
 * bloqueia o request nem o comando de emissão em lote esperando o dompdf.
 */
class GenerateCertificatePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $certificateId)
    {
        //
    }

    public function handle(): void
    {
        $certificate = Certificate::with(['event', 'user'])->find($this->certificateId);

        if (! $certificate) {
            return;
        }

        $pdf = Pdf::loadView('certificates.pdf', ['certificate' => $certificate]);

        $path = "certificates/{$certificate->event_id}/{$certificate->code}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $certificate->path = $path;
        $certificate->save();
    }
}
