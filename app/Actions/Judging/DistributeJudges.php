<?php

namespace App\Actions\Judging;

use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Models\ConflictOfInterest;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Distribui N jurados por submissão (regras-avaliacao):
 *
 * - Só preenche vaga aberta. Atribuição que já existe -- automática ou
 *   manual -- nunca é tocada. Rodar de novo depois de um ajuste manual não
 *   desfaz o ajuste.
 * - Conflito de interesse bloqueia, nunca só avisa: o jurado com conflito
 *   nem entra na lista de elegíveis pra aquela submissão.
 * - Carga balanceada: a cada vaga preenchida, escolhe entre os elegíveis
 *   quem tem menos atribuições no evento até agora -- greedy, não é
 *   otimização global, mas evita "um jurado com o dobro da fila do outro".
 */
class DistributeJudges
{
    /**
     * @param  Collection<int, Submission>|null  $apenasSubmissoes  Restringe a
     *                                                              distribuição a um subconjunto -- usado pra reatribuir a vaga de
     *                                                              um jurado ausente sem reprocessar o evento inteiro.
     * @return array{criadas: int, sem_jurado_elegivel: array<int, string>}
     */
    public function handle(Event $event, ?Collection $apenasSubmissoes = null): array
    {
        $porSubmissao = $event->judges_per_submission;

        $submissoes = $apenasSubmissoes ?? Submission::forEvent($event)
            ->get()
            ->filter(fn (Submission $s) => $s->status->countsForEvaluation());

        $jurados = User::role(Role::Jurado->value)->get();

        if ($jurados->isEmpty() || $submissoes->isEmpty()) {
            return ['criadas' => 0, 'sem_jurado_elegivel' => []];
        }

        $conflitosPorJurado = ConflictOfInterest::query()
            ->whereIn('judge_id', $jurados->pluck('id'))
            ->get()
            ->groupBy('judge_id')
            ->map(fn (Collection $c) => $c->pluck('team_id')->all());

        $cargaPorJurado = JudgeAssignment::forEvent($event)
            ->selectRaw('judge_id, count(*) as total')
            ->groupBy('judge_id')
            ->pluck('total', 'judge_id')
            ->map(fn ($total) => (int) $total)
            ->all();

        $atribuicoesExistentes = JudgeAssignment::forEvent($event)
            ->whereIn('submission_id', $submissoes->pluck('id'))
            ->get()
            ->groupBy('submission_id');

        $criadas = 0;
        $semJuradoElegivel = [];

        foreach ($submissoes as $submissao) {
            $jaAtribuidos = $atribuicoesExistentes->get($submissao->id, collect())->pluck('judge_id')->all();
            $faltam = $porSubmissao - count($jaAtribuidos);

            if ($faltam <= 0) {
                continue;
            }

            $elegiveis = $jurados
                ->reject(fn (User $j) => in_array($j->id, $jaAtribuidos, true))
                ->reject(fn (User $j) => in_array($submissao->team_id, $conflitosPorJurado->get($j->id, []), true))
                ->sortBy(fn (User $j) => $cargaPorJurado[$j->id] ?? 0)
                ->values();

            if ($elegiveis->isEmpty()) {
                $semJuradoElegivel[] = $submissao->title ?? "submissão #{$submissao->id}";

                continue;
            }

            foreach ($elegiveis->take($faltam) as $jurado) {
                $atribuicao = new JudgeAssignment;
                $atribuicao->event_id = $event->id;
                $atribuicao->judge_id = $jurado->id;
                $atribuicao->submission_id = $submissao->id;
                $atribuicao->status = JudgeAssignmentStatus::Pending;
                $atribuicao->assigned_at = now();
                $atribuicao->save();

                $cargaPorJurado[$jurado->id] = ($cargaPorJurado[$jurado->id] ?? 0) + 1;
                $criadas++;
            }

            if ($elegiveis->count() < $faltam) {
                $semJuradoElegivel[] = $submissao->title ?? "submissão #{$submissao->id}";
            }
        }

        return ['criadas' => $criadas, 'sem_jurado_elegivel' => $semJuradoElegivel];
    }
}
