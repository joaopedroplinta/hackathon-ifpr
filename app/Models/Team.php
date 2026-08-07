<?php

namespace App\Models;

use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, SoftDeletes;

    /**
     * event_id, leader_id, invite_code, slug e status ficam de fora: são
     * definidos pelo servidor, nunca pelo formulário.
     */
    protected $fillable = [
        'name',
        'description',
        'track_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TeamStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /** Todas as participações, inclusive de quem já saiu. */
    public function memberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /** Só quem está na equipe agora. */
    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', TeamMemberStatus::Active);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(TeamInvite::class);
    }

    public function hasMember(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->activeMemberships()->where('user_id', $user->id)->exists();
    }

    public function isLeader(?User $user): bool
    {
        return $user !== null && $this->leader_id === $user->id;
    }

    public function activeMemberCount(): int
    {
        return $this->activeMemberships()->count();
    }

    public function isFull(): bool
    {
        return $this->activeMemberCount() >= $this->event->max_team_size;
    }

    /**
     * @param  Builder<Team>  $query
     * @return Builder<Team>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
