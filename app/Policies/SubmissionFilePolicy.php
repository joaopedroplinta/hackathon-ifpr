<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

class SubmissionFilePolicy
{
    /**
     * Baixar. Segue exatamente quem pode ver a submissão — o arquivo mora
     * fora do webroot e só sai por esta porta.
     */
    public function download(User $user, SubmissionFile $file): bool
    {
        return $user->can('view', $file->submission);
    }

    /**
     * Anexar. Mesma janela de edição da submissão, mais o teto de arquivos:
     * quem já não pode mexer no projeto também não sobe arquivo novo.
     */
    public function create(User $user, Submission $submission): Response
    {
        // Delega a janela de edição para a SubmissionPolicy em vez de repetir
        // a regra de prazo aqui: uma regra escrita duas vezes vira duas
        // regras diferentes na primeira alteração.
        $check = Gate::forUser($user)->inspect('update', $submission);

        if (! $check->allowed()) {
            return Response::deny($check->message() ?? 'Você não pode anexar arquivos agora.');
        }

        if ($submission->files()->count() >= Submission::MAX_FILES) {
            return Response::deny(
                'A submissão já tem '.Submission::MAX_FILES.' arquivos. Remova um antes de anexar outro.'
            );
        }

        return Response::allow();
    }

    /**
     * Remover. Organizador não apaga arquivo de equipe por aqui: se precisar,
     * é decisão registrada, não um clique numa tela de participante.
     */
    public function delete(User $user, SubmissionFile $file): Response
    {
        $submission = $file->submission;

        if (! $submission->team->hasMember($user)) {
            return Response::deny('Você não faz parte desta equipe.');
        }

        if ($submission->isSubmitted() && ! $submission->event->submissionIsOpen()) {
            return Response::deny(
                'O prazo de envio já encerrou. Para remover um arquivo, procure a organização.'
            );
        }

        return Response::allow();
    }
}
