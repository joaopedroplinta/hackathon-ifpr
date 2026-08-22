<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Incidents\DeclareIncident;
use App\Enums\IncidentKind;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\DeclareIncidentRequest;
use App\Models\Incident;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncidentController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $this->authorize('viewAny', Incident::class);

        $event = $this->currentEventOrFail();

        $incidentes = Incident::forEvent($event)
            ->with('declaredBy:id,name')
            ->orderByDesc('started_at')
            ->get()
            ->map(fn (Incident $i) => [
                'id' => $i->id,
                'tipo_label' => $i->kind->label(),
                'descricao' => $i->description,
                'extensao_minutos' => $i->deadline_extension_minutes,
                'declarado_por' => $i->declaredBy->name,
                'declarado_em' => $i->started_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
            ])
            ->all();

        return Inertia::render('admin/incidentes/index', [
            'incidentes' => $incidentes,
            'tipos' => array_map(
                fn (IncidentKind $k) => ['value' => $k->value, 'label' => $k->label()],
                IncidentKind::cases(),
            ),
            'prazo_original' => $event->submission_deadline?->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
            'prazo_efetivo' => $event->effectiveSubmissionDeadline()?->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
        ]);
    }

    public function store(DeclareIncidentRequest $request, DeclareIncident $declare): RedirectResponse
    {
        $this->authorize('create', Incident::class);

        $event = $this->currentEventOrFail();
        $kind = IncidentKind::from($request->string('kind')->value());

        $declare->handle(
            $event,
            $request->user(),
            $kind,
            $request->string('description')->value(),
            $request->integer('deadline_extension_minutes'),
        );

        return to_route('painel.incidentes.index')->with('sucesso', 'Incidente registrado.');
    }
}
