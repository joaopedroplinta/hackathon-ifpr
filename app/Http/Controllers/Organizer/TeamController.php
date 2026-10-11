<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\TeamStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\ListTeamsRequest;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lista de equipes do painel -- é por aqui que se desclassifica uma equipe
 * que ainda não enviou nada, caso que a tela de submissão não alcança.
 */
class TeamController extends Controller
{
    use ResolvesParticipation;

    public function index(ListTeamsRequest $request): Response
    {
        $this->authorize('viewAny', Team::class);

        $event = $this->currentEventOrFail();
        $filtros = $request->filtros();

        $equipes = Team::query()
            ->forEvent($event)
            ->with(['track:id,name,color', 'leader:id,name', 'submission:id,team_id,title,status'])
            ->withCount('activeMemberships')
            ->when($filtros['status'], fn ($q, TeamStatus $status) => $q->where('status', $status))
            ->when($filtros['busca'], fn ($q, string $busca) => $q->where('name', 'ilike', '%'.addcslashes($busca, '%_\\').'%'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Team $team) => [
                'id' => $team->id,
                'nome' => $team->name,
                'trilha' => $team->track ? ['nome' => $team->track->name, 'cor' => $team->track->color] : null,
                'lider' => $team->leader?->name,
                'integrantes' => $team->active_memberships_count,
                'status' => $team->status->value,
                'status_label' => $team->status->label(),
                'submissao' => $team->submission ? [
                    'id' => $team->submission->id,
                    'titulo' => $team->submission->title,
                    'status_label' => $team->submission->status->label(),
                ] : null,
                'pode_desclassificar' => $request->user()->can('disqualify', $team),
                'pode_reabilitar' => $request->user()->can('reinstate', $team),
            ]);

        return Inertia::render('admin/equipes/index', [
            'equipes' => $equipes,
            'filtros' => [
                'status' => $filtros['status']?->value,
                'busca' => $filtros['busca'],
            ],
            'opcoes' => [
                'status' => array_map(
                    fn (TeamStatus $status) => ['valor' => $status->value, 'rotulo' => $status->label()],
                    TeamStatus::cases()
                ),
            ],
        ]);
    }
}
