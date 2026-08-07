<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Verificação de e-mail é obrigatória: sem ela, qualquer endereço inventado
 * vira membro de equipe e o organizador não consegue contatar ninguém.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Acesso ao painel administrativo. Papéis acumulam, então a checagem é
     * "tem algum dos papéis de staff", não "o papel é X".
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole([
            Role::Organizador->value,
            Role::Admin->value,
        ]);
    }
}
