<?php

namespace App\Enums;

enum ScheduleItemType: string
{
    case Palestra = 'palestra';
    case Workshop = 'workshop';
    case Checkpoint = 'checkpoint';
    case Refeicao = 'refeicao';
    case Deadline = 'deadline';

    public function label(): string
    {
        return match ($this) {
            self::Palestra => 'Palestra',
            self::Workshop => 'Workshop',
            self::Checkpoint => 'Checkpoint',
            self::Refeicao => 'Refeição',
            self::Deadline => 'Prazo',
        };
    }

    /**
     * Itens de destaque na timeline pública.
     */
    public function isMilestone(): bool
    {
        return in_array($this, [self::Checkpoint, self::Deadline], true);
    }
}
