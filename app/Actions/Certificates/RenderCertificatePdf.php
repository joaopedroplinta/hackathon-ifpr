<?php

namespace App\Actions\Certificates;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Renders a certificate PDF and stores it on the local disk of whichever
 * process runs it. The queue worker and the web service do not share a disk
 * in production (the upload volume is mounted on web only), so the job
 * pre-renders and the download re-renders when the file is not on its disk.
 * The path is derived from the certificate, so both land in the same place.
 */
class RenderCertificatePdf
{
    public function handle(Certificate $certificate): string
    {
        $certificate->loadMissing(['event', 'user']);

        // Nunca foi definido antes -- sem isso, o dompdf cai no padrão dele
        // (carta, retrato), errado pro formato usual de certificado.
        $pdf = Pdf::loadView('certificates.pdf', ['certificate' => $certificate])
            ->setPaper('a4', 'landscape');

        $path = "certificates/{$certificate->event_id}/{$certificate->code}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
