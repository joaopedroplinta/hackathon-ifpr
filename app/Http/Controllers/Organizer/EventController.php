<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Events\UploadRegulation;
use App\Enums\EventStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\UpdateEventRequest;
use App\Http\Requests\Organizer\UploadRegulationRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use ResolvesParticipation;

    public function edit(): Response
    {
        $this->authorize('update', Event::class);

        $event = $this->currentEventOrFail();

        return Inertia::render('admin/evento/editar', [
            'evento' => [
                'name' => $event->name,
                'description' => $event->description,
                'status' => $event->status->value,
                'registration_opens_at' => $event->registration_opens_at?->toIso8601String(),
                'registration_closes_at' => $event->registration_closes_at?->toIso8601String(),
                'starts_at' => $event->starts_at?->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'submission_deadline' => $event->submission_deadline?->toIso8601String(),
                'voting_opens_at' => $event->voting_opens_at?->toIso8601String(),
                'voting_closes_at' => $event->voting_closes_at?->toIso8601String(),
                'min_team_size' => $event->min_team_size,
                'max_team_size' => $event->max_team_size,
                'certificate_signer_name' => $event->certificate_signer_name,
                'certificate_signer_role' => $event->certificate_signer_role,
            ],
            'status_opcoes' => array_map(
                fn (EventStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                EventStatus::cases(),
            ),
            'regulamento' => [
                'nome_arquivo' => $event->regulation_original_name,
                'atualizado_em' => $event->regulation_updated_at?->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i'),
            ],
        ]);
    }

    public function update(UpdateEventRequest $request): RedirectResponse
    {
        $this->authorize('update', Event::class);

        $event = $this->currentEventOrFail();
        $event->fill($request->validated());
        $event->save();

        return to_route('admin.evento.edit')->with('sucesso', 'Evento atualizado.');
    }

    public function uploadRegulation(UploadRegulationRequest $request, UploadRegulation $action): RedirectResponse
    {
        $this->authorize('update', Event::class);

        $event = $this->currentEventOrFail();
        $action->handle($event, $request->file('regulamento'));

        return to_route('admin.evento.edit')->with('sucesso', 'Regulamento atualizado.');
    }
}
