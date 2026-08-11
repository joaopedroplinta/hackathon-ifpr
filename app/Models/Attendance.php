<?php

namespace App\Models;

use App\Enums\AttendanceMethod;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comprovante de presença -- alimenta a carga horária do certificado
 * (PLANO.md). Nada aqui é editável por quem foi marcado presente: quem
 * escreve é sempre a organização, pela Action de check-in.
 */
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory;

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'method' => AttendanceMethod::class,
        ];
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Organizador que confirmou, se houve um -- nulo no lançamento em lote. */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
