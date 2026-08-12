<?php

namespace App\Policies;

use App\Enums\TeamStatus;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Duas regras de prazo convivem aqui, e elas não se contradizem:
 *
 * 1. Envio depois do prazo NÃO é rejeitado. Entra como `late` e o organizador
 *    decide -- rejeitar em silêncio às 23h59 é como se perde uma equipe
 *    (PLANO.md, Anexo A, degrau 1).
 * 2. Quem JÁ enviou não mexe mais na submissão depois do prazo. Reenvio
 *    tardio de quem entregou no horário seria prazo esticado na prática.
 *    Nesse caso a regularização passa pelo organizador.
 */
class SubmissionPolicy
{
    /** Listagem do painel do organizador. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Lançar submissão em nome de uma equipe -- degraus 3 e 4 do plano B,
     * quando a equipe não conseguiu usar o formulário web de jeito nenhum
     * (PLANO.md, Anexo A.2/A.4).
     */
    public function recordManually(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Submission $submission): bool
    {
        return $user->isStaff() || $submission->team->hasMember($user);
    }

    /**
     * Criar a submissão da equipe. Qualquer integrante ativo pode -- no dia
     * do evento o líder pode estar sem bateria, e concentrar o envio em uma
     * pessoa só é ponto único de falha.
     */
    public function create(User $user, Team $team): Response
    {
        if (! $team->hasMember($user)) {
            return Response::deny('Você não faz parte desta equipe.');
        }

        if ($team->status === TeamStatus::Disqualified) {
            return Response::deny('Esta equipe foi desclassificada e não pode enviar projeto.');
        }

        return Response::allow();
    }

    /**
     * Editar, salvar rascunho e reenviar.
     */
    public function update(User $user, Submission $submission): Response
    {
        if ($user->isStaff()) {
            return Response::allow();
        }

        $team = $submission->team;

        if (! $team->hasMember($user)) {
            return Response::deny('Você não faz parte desta equipe.');
        }

        if ($team->status === TeamStatus::Disqualified) {
            return Response::deny('Esta equipe foi desclassificada e não pode alterar o projeto.');
        }

        if ($submission->isSubmitted() && ! $submission->event->submissionIsOpen()) {
            return Response::deny(
                'O prazo de envio já encerrou e o projeto de vocês foi entregue. '
                .'Para corrigir alguma coisa, procure a organização.'
            );
        }

        return Response::allow();
    }
}
