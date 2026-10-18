<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Teams\DisqualifyTeam;
use App\Actions\Teams\ReinstateTeam;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\ChangeTeamStatusRequest;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;

class TeamStatusController extends Controller
{
    use ResolvesParticipation;

    public function disqualify(ChangeTeamStatusRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('disqualify', $team);
        $this->ensureCurrentEvent($team->event_id);

        app(DisqualifyTeam::class)->handle($team, $request->validated('motivo'), $request->user());

        return back()->with('sucesso', 'Equipe desclassificada. Ela sai da avaliação, do ranking, da vitrine e da votação.');
    }

    public function reinstate(ChangeTeamStatusRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('reinstate', $team);
        $this->ensureCurrentEvent($team->event_id);

        app(ReinstateTeam::class)->handle($team, $request->validated('motivo'), $request->user());

        return back()->with('sucesso', 'Equipe reabilitada. Confira a distribuição de jurados e os resultados.');
    }
}
