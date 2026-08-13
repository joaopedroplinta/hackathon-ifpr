<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Regulamento público. Sem Policy, mesmo padrão de RubricController --
 * reduz disputa sobre nota deixar as regras visíveis desde antes do evento
 * (PLANO.md, seção "Regulamento").
 */
class RegulationController extends Controller
{
    public function show(): Response
    {
        $event = Event::current();

        return Inertia::render('publico/regulamento', [
            'evento' => $event ? [
                'nome' => $event->name,
                'min_team_size' => $event->min_team_size,
                'max_team_size' => $event->max_team_size,
                'submission_deadline' => $event->submission_deadline?->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i'),
            ] : null,
            'regulamento' => [
                'tem_arquivo' => $event?->hasRegulationFile() ?? false,
                'nome_arquivo' => $event?->regulation_original_name,
                'atualizado_em' => $event?->regulation_updated_at?->timezone('America/Sao_Paulo')->format('d/m/Y'),
            ],
        ]);
    }

    /**
     * Único caminho de saída do PDF -- storage fora do webroot, sem link
     * direto (.claude/rules/security.md). 404 se não houver evento ou
     * arquivo, em vez de erro de servidor.
     */
    public function download(): StreamedResponse
    {
        $event = Event::current();

        abort_unless($event?->regulation_path, 404);
        abort_unless(Storage::disk('local')->exists($event->regulation_path), 404);

        return Storage::disk('local')->download($event->regulation_path, 'regulamento.pdf');
    }
}
