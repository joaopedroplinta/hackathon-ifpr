<?php

namespace App\Enums;

enum TeamMemberRole: string
{
    case Leader = 'leader';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Leader => 'Líder',
            self::Member => 'Integrante',
        };
    }
}
