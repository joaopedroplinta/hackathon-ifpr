<?php

namespace App\Models;

use App\Enums\CheckpointType;
use Database\Factories\CheckpointFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkpoint extends Model
{
    /** @use HasFactory<CheckpointFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CheckpointType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @param  Builder<Checkpoint>  $query
     * @return Builder<Checkpoint>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }
}
