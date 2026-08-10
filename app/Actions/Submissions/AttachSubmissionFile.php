<?php

namespace App\Actions\Submissions;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class AttachSubmissionFile
{
    /**
     * Grava o arquivo fora do webroot e registra o metadado.
     *
     * O nome no disco é gerado pelo Laravel (hash aleatório); o nome que a
     * pessoa deu vira só metadado. Nome de arquivo vindo do cliente nunca
     * toca o caminho do disco -- .claude/rules/security.md.
     *
     * O disco 'local' do Laravel 12 já aponta para storage/app/private. Não
     * existe disco chamado 'private'.
     */
    public function handle(Submission $submission, User $uploader, UploadedFile $upload): SubmissionFile
    {
        return DB::transaction(function () use ($submission, $uploader, $upload) {
            $path = $upload->store("submissions/{$submission->id}", 'local');

            $file = new SubmissionFile;
            $file->submission_id = $submission->id;
            // Em qual envio este arquivo vai contar pela primeira vez.
            $file->version = $submission->current_version + 1;
            $file->path = $path;
            $file->original_name = $upload->getClientOriginalName();
            // getMimeType() lê o conteúdo; getClientMimeType() confia no que
            // o navegador mandou. Guardamos o que foi verificado.
            $file->mime = $upload->getMimeType() ?? 'application/octet-stream';
            $file->size = $upload->getSize() ?: 0;
            $file->uploaded_by = $uploader->id;
            $file->save();

            return $file;
        });
    }
}
