<?php

namespace App\Enums;

enum JudgeAssignmentStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Não iniciada',
            self::InProgress => 'Em andamento',
            self::Done => 'Concluída',
        };
    }
}
