<?php

namespace App\Enums;

enum TipoVinculo: string
{
    case AlunoIfpr = 'aluno_ifpr';
    case ProfessorIfpr = 'professor_ifpr';
    case Externo = 'externo';

    public function label(): string
    {
        return match ($this) {
            self::AlunoIfpr => 'Aluno do IFPR',
            self::ProfessorIfpr => 'Professor do IFPR',
            self::Externo => 'Externo',
        };
    }

    /** Qual matrícula institucional este vínculo exige, se alguma. */
    public function exigeMatricula(): ?string
    {
        return match ($this) {
            self::AlunoIfpr => 'matricula_suap',
            self::ProfessorIfpr => 'matricula_siape',
            self::Externo => null,
        };
    }
}
