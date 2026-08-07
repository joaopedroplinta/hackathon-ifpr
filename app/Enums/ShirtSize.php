<?php

namespace App\Enums;

enum ShirtSize: string
{
    case PP = 'pp';
    case P = 'p';
    case M = 'm';
    case G = 'g';
    case GG = 'gg';
    case XGG = 'xgg';

    public function label(): string
    {
        return match ($this) {
            self::PP => 'PP',
            self::P => 'P',
            self::M => 'M',
            self::G => 'G',
            self::GG => 'GG',
            self::XGG => 'XGG',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $size) => ['value' => $size->value, 'label' => $size->label()],
            self::cases(),
        );
    }
}
