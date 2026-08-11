<?php

namespace App\Models;

use Database\Factories\EvaluationScoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationScore extends Model
{
    /** @use HasFactory<EvaluationScoreFactory> */
    use HasFactory;

    /** Grava só pelas Actions de avaliação -- ver Evaluation. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }
}
