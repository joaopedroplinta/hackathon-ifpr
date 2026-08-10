<?php

namespace App\Http\Controllers\Participant;

use App\Actions\Submissions\SaveSubmissionDraft;
use App\Actions\Submissions\SubmitSubmission;
use App\Enums\SubmissionStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Participant\SaveSubmissionRequest;
use App\Http\Requests\Participant\SubmitSubmissionRequest;
use App\Models\Event;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubmissionController extends Controller
{
    use ResolvesParticipation;

    public function show(): Response|RedirectResponse
    {
        $event = $this->currentEventOrFail();
        $team = $this->teamOf(request()->user(), $event);

        if (! $team) {
            return to_route('teams.show')
                ->with('erro', 'Você precisa fazer parte de uma equipe antes de enviar um projeto.');
        }

        $submission = $team->submission;

        if ($submission) {
            $this->authorize('view', $submission);
        }

        $check = $submission
            ? Gate::inspect('update', $submission)
            : Gate::inspect('create', [Submission::class, $team]);

        return Inertia::render('submissao/minha', [
            'equipe' => [
                'nome' => $team->name,
                'slug' => $team->slug,
            ],
            'submissao' => $submission ? [
                'title' => $submission->title,
                'summary' => $submission->summary,
                'description' => $submission->description,
                'repo_url' => $submission->repo_url,
                'video_url' => $submission->video_url,
                'deploy_url' => $submission->deploy_url,
                'status' => $submission->status->value,
                'status_label' => $submission->status->label(),
                'enviado_em' => $submission->submitted_at?->toIso8601String(),
                'versao_atual' => $submission->current_version,
                'foi_enviada' => $submission->isSubmitted(),
                'fora_do_prazo' => $submission->status === SubmissionStatus::Late,
            ] : null,
            'arquivos' => $this->filesPayload($submission),
            'prazo' => $this->deadlinePayload($event),
            // A tela não repete a regra de prazo: ela pergunta à Policy e
            // mostra o motivo quando a resposta é não.
            'pode_editar' => $check->allowed(),
            'motivo_bloqueio' => $check->allowed() ? null : $check->message(),
        ]);
    }

    /**
     * Salvar rascunho. Não envia nada e não gera versão.
     */
    public function save(SaveSubmissionRequest $request, SaveSubmissionDraft $saveDraft): RedirectResponse
    {
        $team = $this->teamOrFail();

        $this->authorizeWrite($team);

        $saveDraft->handle($team, $request->validated());

        return to_route('submissions.show')->with('sucesso', 'Rascunho salvo.');
    }

    /**
     * Enviar o projeto. Depois do prazo entra como `late` -- e o participante
     * é avisado disso na resposta, não descobre depois.
     */
    public function submit(SubmitSubmissionRequest $request, SubmitSubmission $submit): RedirectResponse
    {
        $team = $this->teamOrFail();

        $this->authorizeWrite($team);

        $submission = $submit->handle($team, $request->user(), $request->validated());

        if ($submission->status === SubmissionStatus::Late) {
            return to_route('submissions.show')->with(
                'erro',
                'Projeto recebido, mas depois do prazo. Ele foi registrado como envio fora do prazo '
                .'e a organização vai avaliar o caso — procure a equipe organizadora.'
            );
        }

        return to_route('submissions.show')->with(
            'sucesso',
            "Projeto enviado no prazo (versão {$submission->current_version}). Vocês podem reenviar até o fim do prazo."
        );
    }

    /**
     * Arquivos anexados, com a permissão de remoção decidida pela Policy —
     * a tela não repete a regra, ela pergunta.
     *
     * @return array{limite: int, itens: array<int, array{id: int, nome: string, tamanho: string, versao: int, pode_remover: bool}>}
     */
    private function filesPayload(?Submission $submission): array
    {
        if (! $submission) {
            return ['limite' => Submission::MAX_FILES, 'itens' => []];
        }

        return [
            'limite' => Submission::MAX_FILES,
            'itens' => $submission->files()->get()
                ->map(fn (SubmissionFile $file) => [
                    'id' => $file->id,
                    'nome' => $file->original_name,
                    'tamanho' => $file->humanSize(),
                    'versao' => $file->version,
                    'pode_remover' => request()->user()->can('delete', $file),
                ])
                ->all(),
        ];
    }

    /**
     * Criar e editar passam por Policies diferentes: criar olha a equipe,
     * editar olha a submissão que já existe (e o prazo, quando ela já foi
     * enviada).
     */
    private function authorizeWrite(Team $team): void
    {
        $submission = $team->submission;

        if ($submission) {
            $this->authorize('update', $submission);

            return;
        }

        $this->authorize('create', [Submission::class, $team]);
    }

    /**
     * O contador na tela é enfeite: manda a data para exibição, mas quem
     * decide se o prazo virou é o servidor -- .claude/rules/security.md.
     *
     * @return array{encerra_em: string|null, aberto: bool}
     */
    private function deadlinePayload(Event $event): array
    {
        return [
            'encerra_em' => $event->effectiveSubmissionDeadline()?->toIso8601String(),
            'aberto' => $event->submissionIsOpen(),
        ];
    }
}
