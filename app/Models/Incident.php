<?php

namespace App\Models;

use App\Enums\IncidentKind;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Declarado pelo organizador no dia do evento -- PLANO.md, Anexo A.3.
 * Extensão de prazo (`deadline_extension_minutes`) é somada em
 * Event::effectiveSubmissionDeadline(), nunca aplicada por equipe.
 */
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    /** Só a Action DeclareIncident escreve aqui. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'kind' => IncidentKind::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'deadline_extension_minutes' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function declaredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    /**
     * @param  Builder<Incident>  $query
     * @return Builder<Incident>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }
}
