<?php

namespace App\Enums;

/**
 * Por onde a submissão entrou. Ver PLANO.md, Anexo A (plano B).
 * Tudo que não é Web fica marcado no painel até ser conferido.
 */
enum SubmissionSource: string
{
    case Web = 'web';
    case Form = 'form';
    case Email = 'email';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Web => 'Sistema',
            self::Form => 'Formulário externo',
            self::Email => 'E-mail',
            self::Manual => 'Lançamento manual',
        };
    }

    public function needsReview(): bool
    {
        return $this !== self::Web;
    }
}
