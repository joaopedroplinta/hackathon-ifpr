<?php

namespace App\Http\Controllers\Participant;

use App\Enums\Role;
use App\Enums\ShirtSize;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreEventRegistrationRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventRegistrationController extends Controller
{
    public function create(): Response
    {
        $event = $this->currentEventOrFail();

        $this->authorize('register', $event);

        return Inertia::render('inscricao/criar', [
            'tamanhos' => ShirtSize::options(),
        ]);
    }

    public function store(StoreEventRegistrationRequest $request): RedirectResponse
    {
        $event = $this->currentEventOrFail();

        $this->authorize('register', $event);

        DB::transaction(function () use ($request, $event) {
            $registration = new EventRegistration($request->validated());

            // event_id, user_id e registered_at vêm do servidor, nunca do
            // request -- por isso ficam fora do $fillable.
            $registration->event_id = $event->id;
            $registration->user_id = $request->user()->id;
            $registration->registered_at = now();
            $registration->save();

            // Participante é o papel base de quem se inscreve. assignRole é
            // idempotente, então quem já tem o papel não vira duplicata.
            $request->user()->assignRole(Role::Participante->value);
        });

        return to_route('dashboard')
            ->with('sucesso', 'Inscrição confirmada. Agora você já pode formar uma equipe.');
    }

    private function currentEventOrFail(): Event
    {
        return Event::current() ?? throw new NotFoundHttpException('Nenhum evento publicado no momento.');
    }
}
