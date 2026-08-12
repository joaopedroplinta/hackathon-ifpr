<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function index(): Response
    {
        $certificados = Certificate::query()
            ->where('user_id', request()->user()->id)
            ->with('event:id,name')
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $c) => [
                'id' => $c->id,
                'evento' => $c->event->name,
                'tipo_label' => $c->type->label(),
                'pronto' => $c->isReady(),
                'emitido_em' => $c->issued_at->timezone('America/Sao_Paulo')->format('d/m/Y'),
            ])
            ->all();

        return Inertia::render('certificados/index', [
            'certificados' => $certificados,
        ]);
    }

    /**
     * Único caminho de saída do PDF -- storage fora do webroot, sem link
     * direto (.claude/rules/security.md, mesmo padrão de SubmissionFile).
     */
    public function download(Certificate $certificate): StreamedResponse
    {
        $this->authorize('download', $certificate);

        abort_unless($certificate->isReady(), 404);
        abort_unless(Storage::disk('local')->exists($certificate->path), 404);

        return Storage::disk('local')->download($certificate->path, "certificado-{$certificate->type->value}.pdf");
    }
}
