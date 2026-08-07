<?php

namespace App\Enums;

/**
 * Incidente do dia do evento. Ver PLANO.md, Anexo A.3.
 * Extensão de prazo declarada aqui vale para TODAS as equipes.
 */
enum IncidentKind: string
{
    case Rede = 'rede';
    case Sistema = 'sistema';
    case Energia = 'energia';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Rede => 'Queda de rede',
            self::Sistema => 'Sistema fora do ar',
            self::Energia => 'Falta de energia',
            self::Outro => 'Outro',
        };
    }
}
