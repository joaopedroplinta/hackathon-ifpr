<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Submissions\ExportSubmissions;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Enums\TeamStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\ListSubmissionsRequest;
use App\Models\Event;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\SubmissionVersion;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        $submissions = $this->filteredQuery($event, $filtros)
            ->with(['team:id,name,slug,track_id', 'team.track:id,name,color'])
            ->withCount('files')
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
     * Zip com os arquivos de cada equipe e uma planilha de metadados --
     * respeita os mesmos filtros da tela, para o organizador baixar
     * exatamente o que está vendo. Autorização e filtros são os mesmos do
     * `index`: quem vê a lista pode exportar o que está filtrado nela.
     */
    public function export(ListSubmissionsRequest $request, ExportSubmissions $export): BinaryFileResponse
    {
        $this->authorize('viewAny', Submission::class);

        $event = $this->currentEventOrFail();

        $submissions = $this->filteredQuery($event, $request->filtros())
            ->with(['team:id,name,track_id', 'team.track:id,name', 'files'])
            ->orderBy('id')
            ->get();

        $path = $export->handle($submissions);
        $nomeArquivo = 'submissoes-'.$event->slug.'-'.now()->format('Y-m-d-His').'.zip';

        // response()->download(), não Storage::download(): o disco 'local'
        // devolve um StreamedResponse, que não sabe apagar o próprio arquivo
        // depois de enviado. O zip é temporário -- .claude/rules/security.md
        // pede storage fora do webroot, mas não pede acúmulo eterno nele.
        return response()
            ->download(Storage::disk('local')->path($path), $nomeArquivo)
            ->deleteFileAfterSend();
    }

    /**
     * Os três filtros da tela, num só lugar -- `index` e `export` precisam
     * baixar exatamente a mesma lista que a tela mostra.
     *
     * @param  array{status: SubmissionStatus|null, track_id: int|null, busca: string|null}  $filtros
     * @return Builder<Submission>
     */
    private function filteredQuery(Event $event, array $filtros): Builder
    {
        return Submission::query()
            ->forEvent($event)
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
            });
    }

    /**
     * Detalhe de uma submissão: o retrato completo de cada envio (não só o
     * estado atual) e os arquivos que valem hoje. `view` é a mesma Policy que
     * a equipe usa para ver a própria submissão -- staff sempre passa.
     */
    public function show(Submission $submission): Response
    {
        $this->authorize('view', $submission);

        $submission->load(['team:id,name,slug,track_id', 'team.track:id,name,color']);

        return Inertia::render('admin/submissoes/mostrar', [
            'submissao' => [
                'id' => $submission->id,
                'titulo' => $submission->title,
                'resumo' => $submission->summary,
                'descricao' => $submission->description,
                'repo_url' => $submission->repo_url,
                'video_url' => $submission->video_url,
                'deploy_url' => $submission->deploy_url,
                'status' => $submission->status->value,
                'status_label' => $submission->status->label(),
                'origem_label' => $submission->source->label(),
                'precisa_conferencia' => $submission->source->needsReview(),
                'enviado_em' => $submission->submitted_at?->toIso8601String(),
                'versao_atual' => $submission->current_version,
                'equipe' => [
                    'nome' => $submission->team->name,
                    'slug' => $submission->team->slug,
                ],
                'trilha' => $submission->team->track ? [
                    'nome' => $submission->team->track->name,
                    'cor' => $submission->team->track->color,
                ] : null,
            ],
            'versoes' => $submission->versions()
                ->with('author:id,name')
                ->get()
                ->map(fn (SubmissionVersion $version) => [
                    'versao' => $version->version,
                    'autor' => $version->author?->name ?? 'Conta removida',
                    'criado_em' => $version->created_at->toIso8601String(),
                    'payload' => $this->traduzirPayload($version->payload),
                ])
                ->all(),
            'arquivos' => $submission->files()
                ->get()
                ->map(fn (SubmissionFile $file) => [
                    'id' => $file->id,
                    'nome' => $file->original_name,
                    'tamanho' => $file->humanSize(),
                    'versao' => $file->version,
                ])
                ->all(),
        ]);
    }

    /**
     * O payload é o retrato gravado no momento do envio -- ele guarda o
     * *valor* do enum, não o rótulo, porque o rótulo pode mudar de texto no
     * futuro e o retrato histórico não deveria mudar com ele. A tradução
     * para português acontece aqui, na saída, nunca no que fica gravado.
     * `tryFrom` em vez de `from`: um envio antigo não pode quebrar a tela
     * só porque um valor não bate mais com o enum atual.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function traduzirPayload(array $payload): array
    {
        if (isset($payload['status']) && is_string($payload['status'])) {
            $status = SubmissionStatus::tryFrom($payload['status']);
            $payload['status'] = $status?->label() ?? $payload['status'];
        }

        if (isset($payload['source']) && is_string($payload['source'])) {
            $source = SubmissionSource::tryFrom($payload['source']);
            $payload['source'] = $source?->label() ?? $payload['source'];
        }

        return $payload;
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
