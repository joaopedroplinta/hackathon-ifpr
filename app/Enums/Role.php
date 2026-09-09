<?php

namespace App\Enums;

/**
 * Papéis do sistema. Os registros vivem na tabela do spatie/laravel-permission;
 * este enum evita string solta espalhada em Policy e seeder.
 *
 * Jurado, organizador e admin acumulam entre si, mas nenhum deles acumula
 * com participante — ver User::canParticipateAsParticipant().
 */
enum Role: string
{
    case Participante = 'participante';
    case Jurado = 'jurado';
    case Organizador = 'organizador';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Participante => 'Participante',
            self::Jurado => 'Jurado',
            self::Organizador => 'Organizador',
            self::Admin => 'Administrador',
        };
    }

    /**
     * Acesso ao painel administrativo.
     */
    public function isStaff(): bool
    {
        return in_array($this, [self::Organizador, self::Admin], true);
    }

    /**
     * Incompatíveis com participante — ver User::canParticipateAsParticipant()
     * e App\Actions\Users\UpdateUserRoles.
     *
     * @return array<int, string>
     */
    public static function privileged(): array
    {
        return [self::Jurado->value, self::Organizador->value, self::Admin->value];
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
