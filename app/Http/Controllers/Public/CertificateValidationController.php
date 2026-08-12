<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Support\AttendanceHours;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Validação pública de certificado. Sem Policy: o código uuid já é a
 * autorização -- ninguém enumera sequencialmente e encontra certificado de
 * outra pessoa (.claude/rules/security.md).
 */
class CertificateValidationController extends Controller
{
    public function show(string $code, AttendanceHours $attendanceHours): Response
    {
        $certificate = Certificate::query()
            ->where('code', $code)
            ->with(['event', 'user'])
            ->first();

        if (! $certificate) {
            return Inertia::render('publico/validar', ['encontrado' => false]);
        }

        return Inertia::render('publico/validar', [
            'encontrado' => true,
            'nome' => $certificate->user->name,
            'tipo_label' => $certificate->type->label(),
            'evento' => $certificate->event->name,
            'carga_horaria' => $attendanceHours->forUser($certificate->event, $certificate->user),
            'emitido_em' => $certificate->issued_at->timezone('America/Sao_Paulo')->format('d/m/Y'),
        ]);
    }
}
