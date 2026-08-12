<?php

namespace App\Models;

use App\Enums\CertificateType;
use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Emitido por IssueCertificate -- o PDF em si é gerado depois, em fila
 * (GenerateCertificatePdf). `path` fica nulo até o Job terminar.
 */
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    /** Só a Action IssueCertificate escreve aqui. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'type' => CertificateType::class,
            'payload' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<Certificate>  $query
     * @return Builder<Certificate>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }

    public function isReady(): bool
    {
        return $this->path !== null;
    }
}
