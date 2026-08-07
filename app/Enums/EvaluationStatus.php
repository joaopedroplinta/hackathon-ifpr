<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Enviada',
        };
    }

    /**
     * Só avaliação enviada entra no cálculo da nota final.
     * Rascunho existe para o jurado não perder trabalho se a rede cair.
     */
    public function countsForResult(): bool
    {
        return $this === self::Submitted;
    }
}
