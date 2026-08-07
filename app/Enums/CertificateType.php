<?php

namespace App\Enums;

enum CertificateType: string
{
    case Participacao = 'participacao';
    case Colocacao = 'colocacao';
    case Jurado = 'jurado';
    case Mentor = 'mentor';
    case Organizador = 'organizador';

    public function label(): string
    {
        return match ($this) {
            self::Participacao => 'Certificado de participação',
            self::Colocacao => 'Certificado de colocação',
            self::Jurado => 'Certificado de jurado',
            self::Mentor => 'Certificado de mentoria',
            self::Organizador => 'Certificado de organização',
        };
    }

    /**
     * Carga horária vem das presenças registradas, não de valor fixo.
     */
    public function usesAttendanceHours(): bool
    {
        return $this === self::Participacao;
    }
}
