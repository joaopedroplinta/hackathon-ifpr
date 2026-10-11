<?php

namespace App\Actions\Teams;

use App\Actions\Results\RecomputeResults;
use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Models\Result;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamReinstated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

/**
 * Desfaz uma desclassificação: devolve equipe e submissão ao estado que o
 * log da desclassificação guardou, também com motivo e autor no log. Sem
 * registro anterior (status mexido por fora), cai no padrão seguro: equipe
 * confirmada, submissão enviada se já tinha envio, senão rascunho.
 */
class ReinstateTeam
{
    public function handle(Team $team, string $motivo, User $autor): void
    {
        DB::transaction(function () use ($team, $motivo, $autor) {
            $anterior = Activity::query()
                ->forSubject($team)
                ->where('description', 'Equipe desclassificada')
                ->latest('id')
                ->first();

            $statusEquipe = TeamStatus::tryFrom($anterior?->properties['equipe_de'] ?? '');
            $statusEquipe = $statusEquipe && $statusEquipe !== TeamStatus::Disqualified ? $statusEquipe : TeamStatus::Confirmed;

            $team->status = $statusEquipe;
            $team->save();

            $submission = $team->submission;

            if ($submission) {
                $statusSubmissao = SubmissionStatus::tryFrom($anterior?->properties['submissao_de'] ?? '');

                if (! $statusSubmissao || $statusSubmissao === SubmissionStatus::Disqualified) {
                    $statusSubmissao = $submission->submitted_at ? SubmissionStatus::Submitted : SubmissionStatus::Draft;
                }

                $submission->status = $statusSubmissao;
                $submission->save();
            }

            activity()
                ->causedBy($autor)
                ->performedOn($team)
                ->withProperties(['motivo' => $motivo, 'equipe_para' => $statusEquipe->value, 'submissao_para' => $submission?->status->value])
                ->log('Equipe reabilitada');
        });

        $integrantes = User::query()->whereIn('id', $team->activeMemberships()->pluck('user_id'))->get();
        Notification::send($integrantes, new TeamReinstated($team, $motivo));

        if (Result::forEvent($team->event)->exists()) {
            app(RecomputeResults::class)->handle($team->event, $autor);
        }
    }
}
