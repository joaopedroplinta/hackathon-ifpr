<?php

namespace App\Http\Controllers\Public;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lista as edições encerradas com resultado publicado. Mesma regra de
 * segurança do ResultController: `results_published_at` decide, checado no
 * servidor -- edição sem resultado publicado nem aparece aqui, não é só
 * escondida no componente.
 */
class EditionController extends Controller
{
    public function index(): Response
    {
        $edicoes = Event::query()
            ->public()
            ->where('status', EventStatus::Finished)
            ->whereNotNull('results_published_at')
            ->orderByDesc('edition')
            ->get()
            ->map(fn (Event $event) => [
                'nome' => $event->name,
                'edicao' => $event->edition,
                'slug' => $event->slug,
                'encerrado_em' => $event->ends_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('publico/edicoes', [
            'edicoes' => $edicoes,
        ]);
    }
}
