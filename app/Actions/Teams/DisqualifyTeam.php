<?php

namespace App\Actions\Teams;

use App\Actions\Results\RecomputeResults;
use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Models\Result;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Desclassifica a equipe e a submissão dela, com motivo e autor no activity
 * log (.claude/rules/security.md, "Auditoria"). O log guarda o estado
 * anterior dos dois, que é o que ReinstateTeam devolve se a decisão for
 * revista. Se o resultado já foi calculado, recalcula na hora: a página
 * pública lê Result e não pode mostrar uma equipe desclassificada no pódio.
 */
class DisqualifyTeam
{
    public function handle(Team $team, string $motivo, User $autor): void
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

        if (Result::forEvent($team->event)->exists()) {
            app(RecomputeResults::class)->handle($team->event, $autor);
        }
    }
}
