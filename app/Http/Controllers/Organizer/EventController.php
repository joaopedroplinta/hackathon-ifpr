<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\UpdateEventRequest;
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
            ],
            'status_opcoes' => array_map(
                fn (EventStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                EventStatus::cases(),
            ),
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
}
