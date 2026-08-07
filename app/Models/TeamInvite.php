<?php

namespace App\Models;

use Database\Factories\TeamInviteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvite extends Model
{
    /** @use HasFactory<TeamInviteFactory> */
    use HasFactory;

    /**
     * Nada é atribuível em massa: o convite é sempre criado pelo servidor a
     * partir da equipe, de quem convida e do e-mail já validado.
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
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

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Ainda não foi aceito nem expirou. */
    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }
}
