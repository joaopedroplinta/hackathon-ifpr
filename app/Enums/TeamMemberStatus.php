<?php

namespace App\Enums;

enum TeamMemberStatus: string
{
    case Active = 'active';
    case Left = 'left';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Left => 'Saiu da equipe',
        };
    }
}
