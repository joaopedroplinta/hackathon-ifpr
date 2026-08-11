<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\ScheduleItem;
use App\Support\IcsCalendar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Agenda pública. Sem Policy: item não publicado simplesmente não entra na
 * query -- mesmo padrão do LandingController, que também não gate por
 * Policy nenhuma coisa que é pública por natureza.
 */
class AgendaController extends Controller
{
    public function index(): Response
    {
        $event = Event::current();

        return Inertia::render('publico/agenda', [
            'evento' => $event ? ['nome' => $event->name] : null,
            'itens' => $event ? $this->itensPublicados($event)
                ->get()
                ->map(fn (ScheduleItem $item) => [
                    'id' => $item->id,
                    'titulo' => $item->title,
                    'descricao' => $item->description,
                    'tipo' => $item->type->value,
                    'tipo_label' => $item->type->label(),
                    'destaque' => $item->type->isMilestone(),
                    'inicia_em' => $item->starts_at->toIso8601String(),
                    'termina_em' => $item->ends_at->toIso8601String(),
                    'local' => $item->location,
                    'palestrante' => $item->speaker_name,
                    'trilha' => $item->track ? ['nome' => $item->track->name, 'cor' => $item->track->color] : null,
                ])
                ->all() : [],
        ]);
    }

    /**
     * O calendário que qualquer pessoa importa no celular. Mesma regra da
     * tela: só o que está publicado.
     */
    public function ics(IcsCalendar $ics): HttpResponse
    {
        $event = Event::current();

        $conteudo = $ics->build(
            $event ? $this->itensPublicados($event)->get() : collect(),
            $event?->name ?? 'Agenda'
        );

        return response($conteudo, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="agenda.ics"',
        ]);
    }

    /**
     * @return Builder<ScheduleItem>
     */
    private function itensPublicados(Event $event): Builder
    {
        return ScheduleItem::query()
            ->forEvent($event)
            ->published()
            ->with('track:id,name,color')
            ->orderBy('starts_at');
    }
}
