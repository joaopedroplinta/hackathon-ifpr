<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Teams\CreateTeam;
use App\Enums\TeamMemberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreTeamRequest;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamController extends Controller
{
    /**
     * A equipe do usuário no evento atual — ou o convite para criar uma.
     */
    public function show(): Response
    {
        $event = $this->currentEventOrFail();
        $team = $this->teamOf(request()->user(), $event);

        if (! $team) {
            return Inertia::render('equipe/sem-equipe', [
                'pode_criar' => request()->user()->can('create', [Team::class, $event]),
                'inscricoes_abertas' => $event->registrationIsOpen(),
            ]);
        }

        $this->authorize('view', $team);

        $team->load([
            'track:id,name,color',
            'activeMemberships.user:id,name,email,avatar_url',
        ]);

        return Inertia::render('equipe/minha', [
            'equipe' => [
                'nome' => $team->name,
                'descricao' => $team->description,
                'codigo_convite' => $team->invite_code,
                'status' => $team->status->value,
                'status_label' => $team->status->label(),
                'trilha' => $team->track?->only(['id', 'name', 'color']),
                'sou_lider' => $team->isLeader(request()->user()),
                'membros' => $team->activeMemberships->map(fn (TeamMember $m) => [
                    'id' => $m->id,
                    'nome' => $m->user->name,
                    'email' => $m->user->email,
                    'avatar' => $m->user->avatar_url,
                    'papel' => $m->role->label(),
                    'e_lider' => $m->user_id === $team->leader_id,
                ])->values(),
            ],
            'limites' => [
                'minimo' => $event->min_team_size,
                'maximo' => $event->max_team_size,
                'atual' => $team->activeMemberships->count(),
            ],
            'pode_editar' => request()->user()->can('update', $team),
            'convites' => $this->invitesPayload($team),
        ]);
    }

    /**
     * Dados do painel de convite: se o líder pode convidar agora (com o
     * motivo quando não pode) e a lista de convites ainda pendentes.
     *
     * Gate::inspect em vez de can() porque o front precisa do motivo da
     * recusa (prazo encerrado, equipe cheia etc.), não só do booleano --
     * Response::deny já carrega esse texto em português.
     *
     * @return array{pode_convidar: bool, motivo_bloqueio: string|null, pendentes: array<int, array{id: int, email: string, expira_em: string, expirado: bool}>}
     */
    private function invitesPayload(Team $team): array
    {
        $check = Gate::inspect('invite', [$team]);

        return [
            'pode_convidar' => $check->allowed(),
            'motivo_bloqueio' => $check->message(),
            'pendentes' => $team->invites()
                ->whereNull('accepted_at')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (TeamInvite $invite) => [
                    'id' => $invite->id,
                    'email' => $invite->email,
                    'expira_em' => $invite->expires_at->toIso8601String(),
                    'expirado' => $invite->isExpired(),
                ])
                ->values()
                ->all(),
        ];
    }

    public function create(): Response
    {
        $event = $this->currentEventOrFail();

        $this->authorize('create', [Team::class, $event]);

        return Inertia::render('equipe/criar', [
            'trilhas' => $event->tracks()->orderBy('name')->get(['id', 'name', 'description']),
            'limites' => [
                'minimo' => $event->min_team_size,
                'maximo' => $event->max_team_size,
            ],
        ]);
    }

    public function store(StoreTeamRequest $request, CreateTeam $createTeam): RedirectResponse
    {
        $event = $this->currentEventOrFail();

        $this->authorize('create', [Team::class, $event]);

        $team = $createTeam->handle($event, $request->user(), $request->validated());

        return to_route('teams.show')->with(
            'sucesso',
            "Equipe criada. Compartilhe o código {$team->invite_code} com quem for entrar."
        );
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
