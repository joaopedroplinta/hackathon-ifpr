<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Criterion;
use App\Models\Event;
use App\Models\Rubric;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Rubrica pública. Sem Policy, mesmo padrão de AgendaController -- reduz
 * disputa sobre nota deixar os critérios visíveis desde antes do evento
 * começar (PLANO.md, Anexo A).
 */
class RubricController extends Controller
{
    public function show(): Response
    {
        $event = Event::current();

        $rubric = $event
            ? Rubric::forEvent($event)->where('is_active', true)->with('criteria')->first()
            : null;

        return Inertia::render('publico/rubrica', [
            'evento' => $event ? ['nome' => $event->name] : null,
            'criterios' => $rubric
                ? $rubric->criteria
                    ->map(fn (Criterion $c) => [
                        'id' => $c->id,
                        'nome' => $c->name,
                        'descricao' => $c->description,
                        'peso' => (float) $c->weight,
                        'nota_maxima' => $c->max_score,
                    ])
                    ->all()
                : [],
        ]);
    }
}
