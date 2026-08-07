<?php

namespace App\Models;

use App\Enums\ShirtSize;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory;

    /**
     * event_id e user_id ficam de fora: quem define é o servidor, a partir
     * do evento atual e do usuário autenticado — nunca do request.
     */
    protected $fillable = [
        'shirt_size',
        'dietary_notes',
        'phone',
        'course',
    ];

    protected function casts(): array
    {
        return [
            'shirt_size' => ShirtSize::class,
            'registered_at' => 'datetime',
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
}
