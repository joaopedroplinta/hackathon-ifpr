<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreCriterionRequest;
use App\Http\Requests\Organizer\StoreRubricRequest;
use App\Models\Criterion;
use App\Models\Rubric;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * CRUD da rubrica. Só uma fica ativa por evento -- é ela que o jurado
 * avalia e que a tela pública mostra (Public\RubricController).
 */
class RubricController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $this->authorize('viewAny', Rubric::class);

        $event = $this->currentEventOrFail();

        $rubrics = Rubric::forEvent($event)
            ->withCount('criteria')
            ->withSum('criteria', 'weight')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(fn (Rubric $rubric) => [
                'id' => $rubric->id,
                'nome' => $rubric->name,
                'ativa' => $rubric->is_active,
                'total_criterios' => $rubric->criteria_count,
                'soma_pesos' => (float) ($rubric->criteria_sum_weight ?? 0),
            ])
            ->all();

        return Inertia::render('admin/rubrica/index', ['rubricas' => $rubrics]);
    }

    public function store(StoreRubricRequest $request): RedirectResponse
    {
        $this->authorize('create', Rubric::class);

        $event = $this->currentEventOrFail();

        $rubric = new Rubric($request->validated());
        $rubric->event_id = $event->id;
        $rubric->save();

        return to_route('admin.rubrica.show', $rubric)->with('sucesso', "Rubrica \"{$rubric->name}\" criada.");
    }

    public function show(Rubric $rubric): Response
    {
        $this->authorize('viewAny', Rubric::class);

        $rubric->load('criteria');

        return Inertia::render('admin/rubrica/mostrar', [
            'rubrica' => [
                'id' => $rubric->id,
                'nome' => $rubric->name,
                'ativa' => $rubric->is_active,
            ],
            'criterios' => $rubric->criteria
                ->map(fn (Criterion $c) => [
                    'id' => $c->id,
                    'nome' => $c->name,
                    'descricao' => $c->description,
                    'peso' => (float) $c->weight,
                    'nota_maxima' => $c->max_score,
                ])
                ->all(),
        ]);
    }

    /**
     * Ativar uma desativa as outras do mesmo evento -- só uma conta pro
     * cálculo e pra tela do jurado a qualquer momento.
     */
    public function activate(Rubric $rubric): RedirectResponse
    {
        $this->authorize('update', $rubric);

        DB::transaction(function () use ($rubric) {
            Rubric::forEvent($rubric->event)->where('id', '!=', $rubric->id)->update(['is_active' => false]);
            $rubric->is_active = true;
            $rubric->save();
        });

        return to_route('admin.rubrica.index')->with('sucesso', "\"{$rubric->name}\" é a rubrica ativa agora.");
    }

    public function destroy(Rubric $rubric): RedirectResponse
    {
        $this->authorize('delete', $rubric);

        $rubric->delete();

        return to_route('admin.rubrica.index')->with('sucesso', "Rubrica \"{$rubric->name}\" removida.");
    }

    public function storeCriterion(StoreCriterionRequest $request, Rubric $rubric): RedirectResponse
    {
        $this->authorize('update', $rubric);

        $proximaPosicao = $rubric->criteria()->max('position') + 1;

        $criterion = new Criterion($request->validated());
        $criterion->rubric_id = $rubric->id;
        $criterion->position = $proximaPosicao;
        $criterion->save();

        return to_route('admin.rubrica.show', $rubric)->with('sucesso', "Critério \"{$criterion->name}\" adicionado.");
    }

    public function updateCriterion(StoreCriterionRequest $request, Criterion $criterion): RedirectResponse
    {
        $this->authorize('update', $criterion->rubric);

        $criterion->update($request->validated());

        return to_route('admin.rubrica.show', $criterion->rubric)->with('sucesso', "Critério \"{$criterion->name}\" atualizado.");
    }

    public function destroyCriterion(Criterion $criterion): RedirectResponse
    {
        $this->authorize('update', $criterion->rubric);

        $rubric = $criterion->rubric;

        // Nota já lançada é registro histórico -- apagar o critério não pode
        // apagar em silêncio a nota que um jurado já deu nele
        // (.claude/rules/database.md).
        if ($criterion->scores()->exists()) {
            return back()->with('erro', "Critério \"{$criterion->name}\" já tem nota lançada e não pode ser removido.");
        }

        $criterion->delete();

        return to_route('admin.rubrica.show', $rubric)->with('sucesso', "Critério \"{$criterion->name}\" removido.");
    }
}
