<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PopularVote;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vitrine pública dos projetos enviados -- é daqui que o voto popular
 * acontece. Sem Policy: quem pode ver a lista é todo mundo, quem pode
 * votar é decidido por PopularVotePolicy no controller de voto.
 */
class SubmissionShowcaseController extends Controller
{
    public function index(Request $request): Response
    {
        $event = Event::current();

        if (! $event) {
            return Inertia::render('publico/projetos', [
                'evento' => null,
                'submissoes' => [],
                'votacao_aberta' => false,
                'pode_votar' => false,
                'ja_votou_em' => null,
            ]);
        }

        $submissoes = Submission::forEvent($event)
            ->with('team')
            ->get()
            ->filter(fn (Submission $s) => $s->status->countsForEvaluation())
            ->map(fn (Submission $s) => [
                'id' => $s->id,
                'titulo' => $s->title ?? 'Sem título',
                'resumo' => $s->summary,
                'equipe' => $s->team->name,
            ])
            ->values()
            ->all();

        $usuario = $request->user();
        $jaVotouEm = $usuario
            ? PopularVote::forEvent($event)->where('user_id', $usuario->id)->value('submission_id')
            : null;

        return Inertia::render('publico/projetos', [
            'evento' => ['nome' => $event->name],
            'submissoes' => $submissoes,
            'votacao_aberta' => $event->votingIsOpen(),
            'pode_votar' => $usuario !== null && $event->isRegistered($usuario) && $event->votingIsOpen(),
            'ja_votou_em' => $jaVotouEm,
        ]);
    }
}
