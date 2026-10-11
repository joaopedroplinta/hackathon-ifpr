<?php

namespace App\Actions\Results;

use App\Models\Event;
use App\Models\Result;
use App\Models\User;

/**
 * Recalcular antes de publicar é rotina. Depois de publicado, o pódio
 * público muda junto, então o antes e o depois ficam no activity log com o
 * autor (.claude/rules/security.md, "Auditoria").
 */
class RecomputeResults
{
    public function handle(Event $event, User $autor): void
    {
        $jaPublicado = $event->results_published_at !== null;
        $antes = $jaPublicado ? $this->ranking($event) : [];

        app(ComputeResults::class)->handle($event);

        if (! $jaPublicado) {
            return;
        }

        activity()
            ->causedBy($autor)
            ->performedOn($event)
            ->withProperties(['ranking_antes' => $antes, 'ranking_depois' => $this->ranking($event)])
            ->log('Resultado recalculado após a publicação');
    }

    /**
     * @return array<int, int|null> rank geral por submissão
     */
    private function ranking(Event $event): array
    {
        return Result::forEvent($event)->pluck('rank_overall', 'submission_id')->all();
    }
}
