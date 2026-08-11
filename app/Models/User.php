<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
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
     * qr_token fica fora do $fillable de propósito: é o crachá digital do
     * check-in, gerado sozinho na criação -- nunca sequencial nem vindo de
     * formulário -- .claude/rules/security.md. Todo caminho de criação de
     * usuário (cadastro, Google, seeder) passa por aqui, então não precisa
     * ser repetido em cada um.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->qr_token ??= (string) Str::uuid();
        });
    }

    /**
     * @var list<string>
     */
    /**
     * qr_token junto: ele confirma presença física de alguém no evento --
     * vazar por engano num toArray() genérico deixaria qualquer um forjar
     * check-in de outra pessoa. A tela que precisa dele (meu-qr) pede
     * explicitamente, nunca pela serialização padrão do model.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'qr_token',
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

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function judgeAssignments(): HasMany
    {
        return $this->hasMany(JudgeAssignment::class, 'judge_id');
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

    public function isJudge(): bool
    {
        return $this->hasRole(Role::Jurado->value);
    }
}
