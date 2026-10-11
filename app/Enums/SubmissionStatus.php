<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Late = 'late';
    case LateAccepted = 'late_accepted';
    case LateRejected = 'late_rejected';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Submitted => 'Enviado',
            self::Late => 'Enviado fora do prazo',
            self::LateAccepted => 'Fora do prazo — aceito',
            self::LateRejected => 'Fora do prazo — recusado',
            self::Disqualified => 'Desclassificado',
        };
    }

    /**
     * Quem concorre: avaliação, ranking, vitrine e voto. Envio fora do prazo
     * NÃO entra sozinho -- fica visível ao organizador (PLANO.md, Anexo A) e
     * só concorre depois que ele aceita, com motivo no activity log.
     */
    public function countsForEvaluation(): bool
    {
        return in_array($this, [self::Submitted, self::LateAccepted], true);
    }

    /** Já saiu do rascunho, concorrendo ou não: a equipe não reedita depois do prazo. */
    public function isSent(): bool
    {
        return in_array($this, [self::Submitted, self::Late, self::LateAccepted, self::LateRejected], true);
    }

    /** Chegou depois do prazo, aguardando decisão ou já decidido. */
    public function isLate(): bool
    {
        return in_array($this, [self::Late, self::LateAccepted, self::LateRejected], true);
    }
}
