<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Checkins\RegisterAttendance;
use App\Enums\AttendanceMethod;
use App\Enums\CheckpointType;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\ConfirmAttendanceRequest;
use App\Http\Requests\Organizer\StoreCheckpointRequest;
use App\Models\Attendance;
use App\Models\Checkpoint;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Check-in. Dois jeitos de chegar na mesma confirmação: o organizador
 * escaneia o crachá com a própria câmera (abre `show` direto) ou busca o
 * nome em /admin/checkin quando não há QR pra ler. Controle é sempre da
 * organização -- participante nunca confirma a própria presença
 * (AttendancePolicy).
 */
class CheckinController extends Controller
{
    use ResolvesParticipation;

    public function index(Request $request): Response
    {
        $this->authorize('create', Attendance::class);

        $event = $this->currentEventOrFail();
        $busca = trim((string) $request->query('busca', ''));

        return Inertia::render('admin/checkin/index', [
            'checkpoints' => $this->checkpointsPayload($event),
            'opcoes' => [
                'tipos' => array_map(
                    fn (CheckpointType $type) => ['valor' => $type->value, 'rotulo' => $type->label()],
                    CheckpointType::cases()
                ),
            ],
            'busca' => $busca === '' ? null : $busca,
            'resultados' => $busca === '' ? [] : $this->buscarParticipantes($event, $busca),
        ]);
    }

    /** Criação rápida: sem checkpoint nenhum, o check-in inteiro não funciona. */
    public function storeCheckpoint(StoreCheckpointRequest $request): RedirectResponse
    {
        $this->authorize('create', Checkpoint::class);

        $event = $this->currentEventOrFail();

        $checkpoint = new Checkpoint($request->validated());
        $checkpoint->event_id = $event->id;
        $checkpoint->save();

        return to_route('admin.checkin.index')->with('sucesso', "Checkpoint \"{$checkpoint->name}\" criado.");
    }

    /**
     * Tela de confirmação. GET nunca grava nada -- abrir o link (preview de
     * app de mensagem, por exemplo) não pode contar como presença.
     */
    public function show(Request $request, User $user): Response
    {
        $this->authorize('create', Attendance::class);

        $event = $this->currentEventOrFail();
        $checkpoints = Checkpoint::forEvent($event)->orderBy('starts_at')->get();

        $selecionado = $this->resolverCheckpointSelecionado($checkpoints, $request->query('checkpoint'));

        $presenca = $selecionado
            ? Attendance::query()
                ->where('checkpoint_id', $selecionado->id)
                ->where('user_id', $user->id)
                ->with('confirmer:id,name')
                ->first()
            : null;

        $via = $request->query('via') === 'busca' ? 'busca' : null;

        return Inertia::render('admin/checkin/confirmar', [
            'participante' => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->avatar_url,
            ],
            'checkpoints' => $this->checkpointsPayload($event),
            'checkpoint_selecionado_id' => $selecionado?->id,
            'ja_confirmado' => $presenca !== null,
            'confirmado_em' => $presenca?->checked_in_at->toIso8601String(),
            'confirmado_por' => $presenca?->confirmer?->name,
            'via' => $via,
            // A tela nunca vê o qr_token cru -- só a URL pronta pra onde
            // o próprio formulário de confirmação envia (mesmo motivo de
            // buscarParticipantes()).
            'confirmar_url' => route(
                'checkin.store',
                $via ? ['user' => $user->qr_token, 'via' => $via] : ['user' => $user->qr_token]
            ),
        ]);
    }

    public function store(ConfirmAttendanceRequest $request, User $user): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $checkpoint = Checkpoint::findOrFail($request->validated('checkpoint_id'));
        $method = $request->validated('via') === 'busca' ? AttendanceMethod::Manual : AttendanceMethod::Qr;

        [, $criada] = app(RegisterAttendance::class)->handle($checkpoint, $user, $request->user(), $method);

        $parametros = array_filter([
            'user' => $user->qr_token,
            'checkpoint' => $checkpoint->id,
            'via' => $request->validated('via'),
        ]);

        return to_route('checkin.show', $parametros)->with('sucesso', $criada
            ? "Presença de {$user->name} confirmada em \"{$checkpoint->name}\"."
            : "{$user->name} já estava confirmado em \"{$checkpoint->name}\" — nada mudou."
        );
    }

    /**
     * Prioridade: o que veio na URL (link de uma estação fixa) > o
     * checkpoint que está na janela de horário agora > o primeiro que
     * existir. Sempre alguma escolha, nunca tela travada por falta de opção.
     *
     * @param  Collection<int, Checkpoint>  $checkpoints
     */
    private function resolverCheckpointSelecionado(Collection $checkpoints, mixed $idDaQuery): ?Checkpoint
    {
        if ($checkpoints->isEmpty()) {
            return null;
        }

        if ($idDaQuery && $daQuery = $checkpoints->firstWhere('id', (int) $idDaQuery)) {
            return $daQuery;
        }

        $agora = now();
        $ativo = $checkpoints->first(
            fn (Checkpoint $c) => $c->starts_at && $c->ends_at && $agora->between($c->starts_at, $c->ends_at)
        );

        return $ativo ?? $checkpoints->first();
    }

    /**
     * @return array<int, array{id: int, nome: string, tipo_label: string}>
     */
    private function checkpointsPayload(Event $event): array
    {
        return Checkpoint::forEvent($event)
            ->orderBy('starts_at')
            ->get()
            ->map(fn (Checkpoint $c) => [
                'id' => $c->id,
                'nome' => $c->name,
                'tipo_label' => $c->type->label(),
            ])
            ->all();
    }

    /**
     * Só entre quem se inscreveu neste evento -- buscar "qualquer usuário do
     * sistema" vazaria gente de outra edição ou sem relação nenhuma com
     * este hackathon.
     *
     * @return array<int, array{id: int, nome: string, email: string, confirmar_href: string}>
     */
    private function buscarParticipantes(Event $event, string $busca): array
    {
        $termo = '%'.str_replace('%', '\%', $busca).'%';

        return User::query()
            ->whereHas('registrations', fn ($query) => $query->where('event_id', $event->id))
            ->where('name', 'ilike', $termo)
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'nome' => $user->name,
                'email' => $user->email,
                // Nunca o qr_token cru na resposta -- só a URL final. Ver
                // .claude/rules/security.md e User::$hidden.
                'confirmar_href' => route('checkin.show', ['user' => $user->qr_token, 'via' => 'busca']),
            ])
            ->all();
    }
}
