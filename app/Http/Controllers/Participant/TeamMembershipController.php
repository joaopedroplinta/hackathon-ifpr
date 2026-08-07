<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Teams\JoinTeamByCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\JoinTeamRequest;
use App\Models\Event;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamMembershipController extends Controller
{
    /**
     * Formulário com o campo de código. Não há equipe pra autorizar ainda
     * -- a Policy `join` entra em cena só quando o código é resolvido.
     */
    public function create(): Response
    {
        $this->currentEventOrFail();

        return Inertia::render('equipe/entrar');
    }

    public function store(JoinTeamRequest $request, JoinTeamByCode $joinTeamByCode): RedirectResponse
    {
        $event = $this->currentEventOrFail();
        $code = $request->string('invite_code')->toString();

        $team = Team::forEvent($event)->withInviteCode($code)->first();

        if (! $team) {
            // Corrida rara: a equipe sumiu entre a validação do Form
            // Request e o envio. Mesma mensagem de sempre -- nada de
            // caminho diferente para esse caso.
            throw ValidationException::withMessages([
                'invite_code' => 'Código de convite inválido.',
            ]);
        }

        $this->authorize('join', $team);

        $joinTeamByCode->handle($event, $request->user(), $code);

        return to_route('teams.show')
            ->with('sucesso', "Você entrou na equipe {$team->name}.");
    }

    private function currentEventOrFail(): Event
    {
        return Event::current() ?? throw new NotFoundHttpException('Nenhum evento publicado no momento.');
    }
}
