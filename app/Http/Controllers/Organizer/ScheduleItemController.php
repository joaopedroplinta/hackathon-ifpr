<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\ScheduleItemType;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreScheduleItemRequest;
use App\Models\ScheduleItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD da agenda. Painel completo -- diferente do painel de submissões,
 * aqui o organizador escreve mesmo, então cada ação passa pela
 * ScheduleItemPolicy antes de tocar no banco.
 */
class ScheduleItemController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $this->authorize('viewAny', ScheduleItem::class);

        $event = $this->currentEventOrFail();

        $itens = ScheduleItem::query()
            ->forEvent($event)
            ->with('track:id,name,color')
            ->orderBy('starts_at')
            ->get()
            ->map(fn (ScheduleItem $item) => $this->itemPayload($item))
            ->all();

        return Inertia::render('admin/agenda/index', ['itens' => $itens]);
    }

    public function create(): Response
    {
        $this->authorize('create', ScheduleItem::class);

        return Inertia::render('admin/agenda/formulario', [
            'item' => null,
            'opcoes' => $this->opcoes(),
        ]);
    }

    public function store(StoreScheduleItemRequest $request): RedirectResponse
    {
        $this->authorize('create', ScheduleItem::class);

        $event = $this->currentEventOrFail();

        $item = new ScheduleItem($request->validated());
        $item->event_id = $event->id;
        $item->save();

        return to_route('painel.agenda.index')->with('sucesso', "Item \"{$item->title}\" criado como rascunho.");
    }

    public function edit(ScheduleItem $item): Response
    {
        $this->authorize('update', $item);

        return Inertia::render('admin/agenda/formulario', [
            'item' => $this->itemPayload($item, paraFormulario: true),
            'opcoes' => $this->opcoes(),
        ]);
    }

    public function update(StoreScheduleItemRequest $request, ScheduleItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $item->update($request->validated());

        return to_route('painel.agenda.index')->with('sucesso', "Item \"{$item->title}\" atualizado.");
    }

    /** Publicar/despublicar é uma ação isolada -- não exige reenviar o formulário inteiro. */
    public function publish(ScheduleItem $item): RedirectResponse
    {
        $this->authorize('update', $item);

        $item->is_published = ! $item->is_published;
        $item->save();

        return to_route('painel.agenda.index')->with(
            'sucesso',
            $item->is_published ? "\"{$item->title}\" publicado." : "\"{$item->title}\" despublicado."
        );
    }

    public function destroy(ScheduleItem $item): RedirectResponse
    {
        $this->authorize('delete', $item);

        $item->delete();

        return to_route('painel.agenda.index')->with('sucesso', "Item \"{$item->title}\" removido.");
    }

    /**
     * @return array{status: array<int, array{valor: string, rotulo: string}>, trilhas: array<int, array{id: int, nome: string}>}
     */
    private function opcoes(): array
    {
        $event = $this->currentEventOrFail();

        return [
            'tipos' => array_map(
                fn (ScheduleItemType $type) => ['valor' => $type->value, 'rotulo' => $type->label()],
                ScheduleItemType::cases()
            ),
            'trilhas' => $event->tracks()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($track) => ['id' => $track->id, 'nome' => $track->name])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(ScheduleItem $item, bool $paraFormulario = false): array
    {
        if ($paraFormulario) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'type' => $item->type->value,
                'starts_at' => $item->starts_at->toIso8601String(),
                'ends_at' => $item->ends_at->toIso8601String(),
                'location' => $item->location,
                'speaker_name' => $item->speaker_name,
                'speaker_bio' => $item->speaker_bio,
                'track_id' => $item->track_id,
            ];
        }

        return [
            'id' => $item->id,
            'titulo' => $item->title,
            'tipo_label' => $item->type->label(),
            'inicia_em' => $item->starts_at->toIso8601String(),
            'termina_em' => $item->ends_at->toIso8601String(),
            'local' => $item->location,
            'trilha' => $item->track ? ['nome' => $item->track->name, 'cor' => $item->track->color] : null,
            'publicado' => $item->is_published,
        ];
    }
}
