<?php

namespace App\Models;

use App\Enums\ScheduleItemType;
use Database\Factories\ScheduleItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleItem extends Model
{
    /** @use HasFactory<ScheduleItemFactory> */
    use HasFactory;

    /**
     * is_published fica de fora: publicar é ação explícita do organizador,
     * nunca efeito colateral de salvar o formulário -- mesmo espírito de
     * Event::results_published_at.
     */
    protected $fillable = [
        'track_id',
        'title',
        'description',
        'type',
        'starts_at',
        'ends_at',
        'location',
        'speaker_name',
        'speaker_bio',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScheduleItemType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
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

    /**
     * @param  Builder<ScheduleItem>  $query
     * @return Builder<ScheduleItem>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }

    /**
     * @param  Builder<ScheduleItem>  $query
     * @return Builder<ScheduleItem>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
