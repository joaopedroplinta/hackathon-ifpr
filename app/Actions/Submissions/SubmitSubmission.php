<?php

namespace App\Actions\Submissions;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitSubmission
{
    public function __construct(private SaveSubmissionDraft $saveDraft) {}

    /**
     * Envia o projeto e guarda o retrato do que foi enviado.
     *
     * Quem decide se está no prazo é o servidor, comparando now() com o
     * prazo efetivo do evento. Fora do prazo NÃO é recusa: entra como
     * `late` e aparece marcado para o organizador decidir -- rejeitar em
     * silêncio às 23h59 é como se perde uma equipe (PLANO.md, Anexo A).
     *
     * @param  array{title?: string|null, summary?: string|null, description?: string|null, repo_url?: string|null, video_url?: string|null, deploy_url?: string|null}  $data
     */
    public function handle(Team $team, User $author, array $data): Submission
    {
        return DB::transaction(function () use ($team, $author, $data) {
            $submission = $this->saveDraft->handle($team, $data);

            $sentAt = now();
            $onTime = $team->event->submissionIsOpen();

            $submission->status = $onTime ? SubmissionStatus::Submitted : SubmissionStatus::Late;
            $submission->submitted_at = $sentAt;
            $submission->current_version = $submission->current_version + 1;
            $submission->save();

            $version = new SubmissionVersion;
            $version->submission_id = $submission->id;
            $version->version = $submission->current_version;
            $version->created_by = $author->id;
            $version->payload = [
                'title' => $submission->title,
                'summary' => $submission->summary,
                'description' => $submission->description,
                'repo_url' => $submission->repo_url,
                'video_url' => $submission->video_url,
                'deploy_url' => $submission->deploy_url,
                'status' => $submission->status->value,
                'source' => SubmissionSource::Web->value,
                'submitted_at' => $sentAt->toIso8601String(),
                // Quais arquivos valiam neste envio. Sem isto o histórico
                // diria o que a equipe escreveu, mas não o que ela entregou.
                'files' => $submission->files()->get()
                    ->map(fn ($file) => [
                        'id' => $file->id,
                        'original_name' => $file->original_name,
                        'mime' => $file->mime,
                        'size' => $file->size,
                    ])
                    ->all(),
            ];
            $version->save();

            return $submission;
        });
    }
}
