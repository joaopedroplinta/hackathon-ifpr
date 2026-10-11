<?php

namespace App\Actions\Teams;

use App\Actions\Results\RecomputeResults;
use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TeamDisqualified;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Desclassifica a equipe e a submissão dela, com motivo e autor no activity
 * log (.claude/rules/security.md, "Auditoria"). O log guarda o estado
 * anterior dos dois, que é o que ReinstateTeam devolve se a decisão for
 * revista. Se o resultado já foi calculado, recalcula na hora: a página
 * pública lê Result e não pode mostrar uma equipe desclassificada no pódio.
 */
class DisqualifyTeam
{
    /**
     * `$notificar = false` só para quem monta um cenário (a demonstração): a
     * desclassificação já vem pronta e não deve disparar e-mail a cada reinício.
     */
    public function handle(Team $team, string $motivo, User $autor, bool $notificar = true): void
    {
        DB::transaction(function () use ($team, $motivo, $autor) {
            $submission = $team->submission;

            $propriedades = [
                'motivo' => $motivo,
                'equipe_de' => $team->status->value,
                'submissao_de' => $submission?->status->value,
            ];

            $team->status = TeamStatus::Disqualified;
            $team->save();

            if ($submission) {
                $submission->status = SubmissionStatus::Disqualified;
                $submission->save();
            }

            activity()
                ->causedBy($autor)
                ->performedOn($team)
                ->withProperties($propriedades)
                ->log('Equipe desclassificada');
        });

        if ($notificar) {
            $integrantes = User::query()->whereIn('id', $team->activeMemberships()->pluck('user_id'))->get();
            Notification::send($integrantes, new TeamDisqualified($team, $motivo));
        }

        // Sempre recalcula, mesmo sem linha de resultado hoje: se esta era a
        // única equipe concorrendo, desclassificá-la esvazia a tabela, e a
        // reabilitação não teria como saber que havia resultado a refazer.
        app(RecomputeResults::class)->handle($team->event, $autor);
    }
}
