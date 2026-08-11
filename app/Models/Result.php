<?php

namespace App\Models;

use Database\Factories\ResultFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Materializado por ComputeResults -- nunca calculado na hora que a página
 * pública é aberta. Recalcular é idempotente: roda de novo e atualiza a
 * mesma linha, não duplica (PLANO.md, seção 4).
 */
class Result extends Model
{
    /** @use HasFactory<ResultFactory> */
    use HasFactory;

    /** Só a Action ComputeResults escreve aqui. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'final_score' => 'decimal:2',
            'criteria_breakdown' => 'array',
            'rank_overall' => 'integer',
            'rank_track' => 'integer',
            'popular_votes_count' => 'integer',
            'computed_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    /**
     * @param  Builder<Result>  $query
     * @return Builder<Result>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }
}
