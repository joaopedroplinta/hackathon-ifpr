<?php

namespace App\Actions\Submissions;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\Team;

class SaveSubmissionDraft
{
    /**
     * Cria ou atualiza o rascunho da equipe.
     *
     * Rascunho não gera versão: versão é retrato de envio, e salvar o que
     * ainda está sendo escrito encheria o histórico de ruído. O que a equipe
     * mandou de fato vive em submission_versions -- ver SubmitSubmission.
     *
     * @param  array{title?: string|null, summary?: string|null, description?: string|null, repo_url?: string|null, video_url?: string|null, deploy_url?: string|null}  $data
     */
    public function handle(Team $team, array $data): Submission
    {
        $submission = $team->submission ?? new Submission;

        $submission->fill($data);

        if (! $submission->exists) {
            $submission->event_id = $team->event_id;
            $submission->team_id = $team->id;
            $submission->status = SubmissionStatus::Draft;
            $submission->source = SubmissionSource::Web;
        }

        $submission->save();

        // A equipe pode ter sido carregada antes desta escrita.
        $team->setRelation('submission', $submission);

        return $submission;
    }
}
