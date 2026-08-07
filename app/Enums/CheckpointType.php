<?php

namespace App\Enums;

enum CheckpointType: string
{
    case Entrada = 'entrada';
    case Dia = 'dia';
    case Oficina = 'oficina';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada no evento',
            self::Dia => 'Presença do dia',
            self::Oficina => 'Oficina',
        };
    }
}
