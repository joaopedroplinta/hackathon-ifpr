<?php

namespace App\Models;

use Database\Factories\RubricFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    /** @use HasFactory<RubricFactory> */
    use HasFactory;

    /**
     * is_active fica de fora: virar a rubrica ativa é uma troca que afeta
     * o evento inteiro (só uma por vez), não um campo de formulário comum
     * -- ver RubricController::activate().
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(Criterion::class)->orderBy('position');
    }

    /**
     * @param  Builder<Rubric>  $query
     * @return Builder<Rubric>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }
}
