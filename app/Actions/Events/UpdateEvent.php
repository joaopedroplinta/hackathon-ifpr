<?php

namespace App\Actions\Events;

use App\Models\Event;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonInterface;

/**
 * Edita o evento e deixa no activity log qualquer mudança de prazo de
 * submissão, janela de voto ou status -- decide quem envia a tempo e quem
 * ganha (.claude/rules/security.md, "Prazo" e "Auditoria"). Mudança de nome,
 * descrição etc. não entra no log.
 */
class UpdateEvent
{
    private const AUDITADOS = ['status', 'submission_deadline', 'voting_opens_at', 'voting_closes_at'];

    /**
     * @param  array<string, mixed>  $dados
     */
    public function handle(Event $event, array $dados, User $autor): void
    {
        $event->fill($dados);

        $alteracoes = [];
        foreach (self::AUDITADOS as $campo) {
            if ($event->isDirty($campo)) {
                $alteracoes[$campo] = [
                    'de' => $this->valor($event->getOriginal($campo)),
                    'para' => $this->valor($event->getAttribute($campo)),
                ];
            }
        }

        $event->save();

        if ($alteracoes === []) {
            return;
        }

        activity()
            ->causedBy($autor)
            ->performedOn($event)
            ->withProperties(['alteracoes' => $alteracoes])
            ->log('Prazo, votação ou status do evento alterados');
    }

    private function valor(mixed $valor): mixed
    {
        return match (true) {
            $valor instanceof BackedEnum => $valor->value,
            $valor instanceof CarbonInterface => $valor->copy()->utc()->toIso8601String(),
            default => $valor,
        };
    }
}
