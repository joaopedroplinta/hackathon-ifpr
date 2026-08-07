<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Teams\TransferLeadership;
use App\Enums\TeamMemberStatus;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TeamLeadershipController extends Controller
{
    public function update(Request $request, Team $team, TransferLeadership $transfer): RedirectResponse
    {
        $this->authorize('transferLeadership', $team);

        $validated = $request->validate([
            'membership_id' => ['required', 'integer'],
        ], [], ['membership_id' => 'integrante']);

        // A busca já parte da equipe e do status ativo. Um id de outra
        // equipe simplesmente não é encontrado -- nunca vira autorização.
        $novoLider = $team->activeMemberships()
            ->where('id', $validated['membership_id'])
            ->where('status', TeamMemberStatus::Active)
            ->first();

        if (! $novoLider instanceof TeamMember || $novoLider->user_id === $team->leader_id) {
            throw ValidationException::withMessages([
                'membership_id' => 'Selecione outro integrante ativo da equipe.',
            ]);
        }

        $transfer->handle($team, $novoLider);

        return to_route('teams.show')->with(
            'sucesso',
            "{$novoLider->user->name} agora é o líder da equipe."
        );
    }
}
