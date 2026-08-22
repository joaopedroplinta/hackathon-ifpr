<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Events\UploadRegulation;
use App\Enums\EventStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreEventRequest;
use App\Http\Requests\Organizer\UpdateEventRequest;
use App\Http\Requests\Organizer\UploadRegulationRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    use ResolvesParticipation;

    /**
     * Sem evento nenhum ainda, manda pra criação em vez de 404 --
     * `currentEventOrFail()` é o que toda outra tela do organizador usa, e
     * um 404 mudo não diz ao organizador que o próximo passo é criar o
     * primeiro evento.
     */
    public function edit(): Response|RedirectResponse
    {
        $this->authorize('update', Event::class);

        $event = Event::current();

        if (! $event) {
            return to_route('admin.evento.create');
        }

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
            'regulamento' => [
                'nome_arquivo' => $event->regulation_original_name,
                'atualizado_em' => $event->regulation_updated_at?->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i'),
            ],
        ]);
    }

    public function create(): Response|RedirectResponse
    {
        $this->authorize('create', Event::class);

        if (Event::current()) {
            return to_route('admin.evento.edit');
        }

        return Inertia::render('admin/evento/criar');
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $this->authorize('create', Event::class);

        $edition = (Event::max('edition') ?? 0) + 1;

        Event::create([
            ...$request->validated(),
            // Sempre a próxima edição, nunca escolhida à mão -- evita
            // colisão e mantém `Event::current()` (ordena por edition)
            // determinístico.
            'edition' => $edition,
            'status' => EventStatus::Published,
            // slug é NOT NULL + unique; incluir a edição garante
            // unicidade mesmo se o nome se repetir entre edições.
            'slug' => Str::slug($request->validated('name')).'-'.$edition,
        ]);

        return to_route('admin.evento.edit')->with('sucesso', 'Evento criado.');
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
