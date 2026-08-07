<?php

namespace App\Enums;

enum EventStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Running = 'running';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Published => 'Publicado',
            self::Running => 'Em andamento',
            self::Finished => 'Encerrado',
        };
    }

    /**
     * Evento em rascunho não aparece em nenhuma página pública.
     */
    public function isPublic(): bool
    {
        return $this !== self::Draft;
    }
}
