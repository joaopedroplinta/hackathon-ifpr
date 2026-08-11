<?php

namespace App\Models;

use Database\Factories\PopularVoteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prêmio separado, nunca soma na nota técnica nem desempata a técnica
 * (regras-avaliacao). Grava só pelo Participant\PopularVoteController.
 */
class PopularVote extends Model
{
    /** @use HasFactory<PopularVoteFactory> */
    use HasFactory;

    protected $fillable = [];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<PopularVote>  $query
     * @return Builder<PopularVote>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }
}
