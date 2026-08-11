<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    /**
     * Nada é atribuível em massa: quem grava é sempre SaveEvaluationDraft ou
     * SubmitEvaluation, nunca um formulário livre -- .claude/rules/security.md.
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'status' => EvaluationStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(JudgeAssignment::class, 'assignment_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }
}
