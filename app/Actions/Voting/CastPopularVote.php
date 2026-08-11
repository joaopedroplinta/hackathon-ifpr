<?php

namespace App\Actions\Voting;

use App\Models\Event;
use App\Models\PopularVote;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class CastPopularVote
{
    /**
     * Checa antes de gravar -- resolve o caso comum sem tentar um insert
     * fadado ao fracasso. O índice único (event_id, user_id) ainda é quem
     * garante 1 voto por pessoa sob corrida de duplo clique
     * (.claude/rules/security.md); o catch cobre exatamente esse caso raro.
     *
     * @return array{0: PopularVote, 1: bool} O voto e se foi registrado agora.
     */
    public function handle(Event $event, Submission $submission, User $user): array
    {
        $existente = PopularVote::forEvent($event)->where('user_id', $user->id)->first();

        if ($existente) {
            return [$existente, false];
        }

        $vote = new PopularVote;
        $vote->event_id = $event->id;
        $vote->submission_id = $submission->id;
        $vote->user_id = $user->id;

        try {
            $vote->save();
        } catch (UniqueConstraintViolationException) {
            return [
                PopularVote::forEvent($event)->where('user_id', $user->id)->firstOrFail(),
                false,
            ];
        }

        return [$vote, true];
    }
}
