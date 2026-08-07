<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Teams\AcceptTeamInvite;
use App\Actions\Teams\InviteToTeam;
use App\Enums\TeamMemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreTeamInviteRequest;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamInviteController extends Controller
{
    /**
     * Envia o convite. Só o líder, só dentro do prazo -- tudo checado na
     * TeamPolicy::invite, com o e-mail já validado pelo Form Request.
     */
    public function store(StoreTeamInviteRequest $request, InviteToTeam $inviteToTeam): RedirectResponse
    {
        $event = $this->currentEventOrFail();
        $team = $this->teamOf($request->user(), $event)
            ?? throw new NotFoundHttpException('Você não tem uma equipe neste evento.');

        $email = $request->validated('email');

        $this->authorize('invite', [$team, $email]);

        $invite = $inviteToTeam->handle($team, $request->user(), $email);

        return to_route('teams.show')->with('sucesso', "Convite enviado para {$invite->email}.");
    }

    /**
     * Aceita o convite a partir do link do e-mail. Rota com route model
     * binding por token: token inexistente cai direto em 404.
     *
     * Usa Gate::inspect em vez de $this->authorize(): quem chega aqui
     * clicou num link de e-mail, não enviou um formulário, então um 403 cru
     * seria uma tela ruim pra convite expirado ou já aceito. A Policy
     * continua sendo quem decide -- só a forma de reagir à recusa muda,
     * com Response::deny virando mensagem de erro na tela em vez de
     * abortar a request.
     */
    public function accept(TeamInvite $invite, AcceptTeamInvite $acceptTeamInvite): RedirectResponse
    {
        $check = Gate::inspect('accept', $invite);

        if (! $check->allowed()) {
            return to_route('teams.show')->with('erro', $check->message());
        }

        $acceptTeamInvite->handle($invite, request()->user());

        return to_route('teams.show')->with('sucesso', 'Convite aceito! Você agora faz parte da equipe.');
    }

    private function teamOf(User $user, Event $event): ?Team
    {
        $membership = TeamMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', TeamMemberStatus::Active)
            ->first();

        return $membership?->team;
    }

    private function currentEventOrFail(): Event
    {
        return Event::current() ?? throw new NotFoundHttpException('Nenhum evento publicado no momento.');
    }
}
