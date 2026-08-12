<?php

namespace App\Actions\Submissions;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Grava uma submissão que não veio do formulário web -- import de CSV
 * (degrau 2) ou lançamento manual pelo organizador (degraus 3 e 4). Usada
 * tanto pelo comando quanto pelo controller (.claude/rules/estrutura.md:
 * "regra que precisa rodar de controller e de comando artisan").
 *
 * Nunca sobrescreve uma submissão que já conta pra avaliação -- devolve
 * null e quem chamou decide o que fazer com o conflito (Anexo A.4: "relatório
 * pra decisão humana, nunca sobrescrita automática").
 */
class RecordExternalSubmission
{
    public function __construct(private SaveSubmissionDraft $saveDraft) {}

    /**
     * @param  array{title?: string|null, summary?: string|null, description?: string|null, repo_url?: string|null, video_url?: string|null, deploy_url?: string|null}  $data
     */
    public function handle(
        Team $team,
        array $data,
        SubmissionSource $source,
        ?User $recordedBy,
        Carbon $originalSubmittedAt,
    ): ?Submission {
        if ($team->submission?->isSubmitted()) {
            return null;
        }

        $submission = $this->saveDraft->handle($team, $data);

        $deadline = $team->event->effectiveSubmissionDeadline();
        $onTime = $deadline === null || $originalSubmittedAt->lte($deadline);

        $submission->status = $onTime ? SubmissionStatus::Submitted : SubmissionStatus::Late;
        $submission->submitted_at = now();
        $submission->original_submitted_at = $originalSubmittedAt;
        $submission->source = $source;
        $submission->recorded_by = $recordedBy?->id;
        $submission->current_version = $submission->current_version + 1;
        $submission->save();

        $version = new SubmissionVersion;
        $version->submission_id = $submission->id;
        $version->version = $submission->current_version;
        $version->created_by = $recordedBy?->id ?? $team->leader_id;
        $version->payload = [
            'title' => $submission->title,
            'summary' => $submission->summary,
            'description' => $submission->description,
            'repo_url' => $submission->repo_url,
            'video_url' => $submission->video_url,
            'deploy_url' => $submission->deploy_url,
            'status' => $submission->status->value,
            'source' => $source->value,
            'submitted_at' => $originalSubmittedAt->toIso8601String(),
            'files' => [],
        ];
        $version->save();

        return $submission;
    }
}
