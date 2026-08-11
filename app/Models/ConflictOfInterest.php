<?php

namespace App\Models;

use Database\Factories\ConflictOfInterestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bloqueia atribuição, não é só um aviso -- DistributeJudges e o ajuste
 * manual checam esta tabela antes de criar qualquer JudgeAssignment
 * (regras-avaliacao).
 */
class ConflictOfInterest extends Model
{
    /** @use HasFactory<ConflictOfInterestFactory> */
    use HasFactory;

    protected $table = 'conflicts_of_interest';

    protected $fillable = [
        'reason',
    ];

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
