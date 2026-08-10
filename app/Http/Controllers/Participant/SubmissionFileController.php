<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Submissions\AttachSubmissionFile;
use App\Actions\Submissions\SaveSubmissionDraft;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\StoreSubmissionFileRequest;
use App\Models\Submission;
use App\Models\SubmissionFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionFileController extends Controller
{
    use ResolvesParticipation;

    public function store(
        StoreSubmissionFileRequest $request,
        SaveSubmissionDraft $saveDraft,
        AttachSubmissionFile $attach,
    ): RedirectResponse {
        $team = $this->teamOrFail();
        $submission = $team->submission;

        // Anexar antes de escrever qualquer coisa é caso normal: a equipe
        // sobe o slide primeiro e escreve o resumo depois. Abre o rascunho
        // vazio em vez de exigir que preencham algo antes.
        if (! $submission) {
            $this->authorize('create', [Submission::class, $team]);
            $submission = $saveDraft->handle($team, []);
        }

        $this->authorize('create', [SubmissionFile::class, $submission]);

        $file = $attach->handle($submission, $request->user(), $request->file('arquivo'));

        return to_route('submissions.show')
            ->with('sucesso', "Arquivo \"{$file->original_name}\" anexado.");
    }

    /**
     * Único caminho de saída do arquivo. O storage fica fora do webroot e
     * não existe link direto -- .claude/rules/security.md.
     */
    public function download(SubmissionFile $file): StreamedResponse
    {
        $this->authorize('download', $file);

        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function destroy(SubmissionFile $file): RedirectResponse
    {
        $this->authorize('delete', $file);

        // Soft delete e o arquivo continua no disco de propósito: se o
        // resultado for contestado, o que foi enviado precisa ser
        // reconstituível. Limpeza de disco é decisão do organizador, não
        // efeito colateral de um clique da equipe.
        $file->delete();

        return to_route('submissions.show')
            ->with('sucesso', "Arquivo \"{$file->original_name}\" removido.");
    }
}
