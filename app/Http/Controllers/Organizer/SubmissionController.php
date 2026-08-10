<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\ListSubmissionsRequest;
use App\Models\Event;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Painel de submissões do organizador. Só leitura: conferir o que chegou,
 * quem ainda não enviou e o que veio de fora do sistema. Mudar situação de
 * submissão (aceitar envio fora do prazo, desclassificar) exige justificativa
 * e auditoria e fica no painel completo -- PLANO.md, semanas 6-7.
 */
class SubmissionController extends Controller
{
    use ResolvesParticipation;

    public function index(ListSubmissionsRequest $request): Response
    {
        $this->authorize('viewAny', Submission::class);

        $event = $this->currentEventOrFail();
        $filtros = $request->filtros();

        $submissions = Submission::query()
            ->forEvent($event)
            ->with(['team:id,name,slug,track_id', 'team.track:id,name,color'])
            ->withCount('files')
            ->when($filtros['status'], fn (Builder $query, SubmissionStatus $status) => $query->where('status', $status))
            ->when(
                $filtros['track_id'],
                fn (Builder $query, int $trackId) => $query->whereHas('team', fn (Builder $team) => $team->where('track_id', $trackId))
            )
            ->when($filtros['busca'], function (Builder $query, string $busca) {
                // Buscar pelo nome da equipe e pelo título: no dia do evento o
                // organizador ouve um dos dois, nunca sabe qual.
                $termo = '%'.str_replace('%', '\%', $busca).'%';

                return $query->where(
                    fn (Builder $inner) => $inner
                        ->where('title', 'ilike', $termo)
                        ->orWhereHas('team', fn (Builder $team) => $team->where('name', 'ilike', $termo))
                );
            })
            // Sem envio primeiro é a ordem errada aqui: o que o organizador
            // confere é o que chegou, e o mais recente é o que ele ainda não viu.
            ->orderByRaw('submitted_at desc nulls last')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Submission $submission) => [
                'id' => $submission->id,
                'titulo' => $submission->title,
                'equipe' => [
                    'nome' => $submission->team->name,
                    'slug' => $submission->team->slug,
                ],
                'trilha' => $submission->team->track ? [
                    'nome' => $submission->team->track->name,
                    'cor' => $submission->team->track->color,
                ] : null,
                'status' => $submission->status->value,
                'status_label' => $submission->status->label(),
                'origem_label' => $submission->source->label(),
                // Submissão que não entrou pelo sistema fica marcada até ser
                // conferida -- .claude/rules/security.md.
                'precisa_conferencia' => $submission->source->needsReview(),
                'enviado_em' => $submission->submitted_at?->toIso8601String(),
                'versao_atual' => $submission->current_version,
                'arquivos' => $submission->files_count,
            ]);

        return Inertia::render('admin/submissoes/index', [
            'submissoes' => $submissions,
            'filtros' => [
                'status' => $filtros['status']?->value,
                'track_id' => $filtros['track_id'],
                'busca' => $filtros['busca'],
            ],
            'opcoes' => [
                'status' => array_map(
                    fn (SubmissionStatus $status) => ['valor' => $status->value, 'rotulo' => $status->label()],
                    SubmissionStatus::cases()
                ),
                'trilhas' => $event->tracks()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($track) => ['id' => $track->id, 'nome' => $track->name])
                    ->all(),
            ],
            'resumo' => $this->resumo($event),
        ]);
    }

    /**
     * O que o organizador olha primeiro na véspera do prazo: quantas chegaram,
     * em que situação, e — a pergunta que importa — quem ainda não enviou.
     *
     * @return array{total: int, por_status: array<int, array{valor: string, rotulo: string, total: int}>, equipes_sem_envio: array<int, string>}
     */
    private function resumo(Event $event): array
    {
        $contagem = Submission::query()
            ->forEvent($event)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $semEnvio = Team::query()
            ->forEvent($event)
            ->where('status', '!=', TeamStatus::Disqualified)
            ->whereDoesntHave(
                'submission',
                fn (Builder $query) => $query->whereIn('status', [SubmissionStatus::Submitted, SubmissionStatus::Late])
            )
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return [
            'total' => (int) $contagem->sum(),
            'por_status' => array_map(
                fn (SubmissionStatus $status) => [
                    'valor' => $status->value,
                    'rotulo' => $status->label(),
                    'total' => (int) ($contagem[$status->value] ?? 0),
                ],
                SubmissionStatus::cases()
            ),
            'equipes_sem_envio' => $semEnvio,
        ];
    }
}
