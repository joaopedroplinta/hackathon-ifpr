<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Late = 'late';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Enviado',
            self::Late => 'Enviado fora do prazo',
            self::Disqualified => 'Desclassificado',
        };
    }

    /**
     * Envio após o prazo entra como Late e fica visível ao organizador,
     * em vez de ser rejeitado em silêncio. Ver PLANO.md, Anexo A.
     */
    public function countsForEvaluation(): bool
    {
        return in_array($this, [self::Submitted, self::Late], true);
    }
}
