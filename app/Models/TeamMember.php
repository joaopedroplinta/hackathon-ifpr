<?php

namespace App\Models;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use Database\Factories\TeamMemberFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    /** @use HasFactory<TeamMemberFactory> */
    use HasFactory;

    /**
     * Nada é atribuível em massa: toda participação é criada pelo servidor
     * a partir do usuário autenticado e da equipe resolvida.
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'role' => TeamMemberRole::class,
            'status' => TeamMemberStatus::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === TeamMemberStatus::Active;
    }
}
