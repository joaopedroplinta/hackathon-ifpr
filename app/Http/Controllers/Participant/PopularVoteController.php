<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Voting\CastPopularVote;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StorePopularVoteRequest;
use App\Models\PopularVote;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;

class PopularVoteController extends Controller
{
    use ResolvesParticipation;

    public function store(StorePopularVoteRequest $request): RedirectResponse
    {
        $event = $this->currentEventOrFail();
        $this->authorize('create', [PopularVote::class, $event]);

        $submission = Submission::findOrFail($request->validated('submission_id'));

        [, $registradoAgora] = app(CastPopularVote::class)->handle($event, $submission, $request->user());

        if (! $registradoAgora) {
            return back()->with('erro', 'Você já votou neste evento.');
        }

        return back()->with('sucesso', 'Voto registrado. Obrigado por participar!');
    }
}
