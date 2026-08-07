<?php

namespace App\Enums;

enum TeamStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Disqualified = 'disqualified';

    /**
     * Texto exibido ao usuário. Enum nunca aparece cru na tela.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Em formação',
            self::Confirmed => 'Confirmada',
            self::Disqualified => 'Desclassificada',
        };
    }

    /**
     * Equipe desclassificada não submete nem aparece no ranking.
     */
    public function isActive(): bool
    {
        return $this !== self::Disqualified;
    }
}
