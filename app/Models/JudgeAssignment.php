<?php

namespace App\Models;

use App\Enums\JudgeAssignmentStatus;
use Database\Factories\JudgeAssignmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JudgeAssignment extends Model
{
    /** @use HasFactory<JudgeAssignmentFactory> */
    use HasFactory;

    /**
     * Nada é atribuível em massa: quem atribui um jurado a uma submissão é
     * sempre a Action de distribuição ou o ajuste manual do organizador,
     * nunca um formulário livre -- .claude/rules/security.md.
     */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'status' => JudgeAssignmentStatus::class,
            'assigned_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }

    /**
     * @param  Builder<JudgeAssignment>  $query
     * @return Builder<JudgeAssignment>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }

    /**
     * @param  Builder<JudgeAssignment>  $query
     * @return Builder<JudgeAssignment>
     */
    public function scopeForJudge(Builder $query, User $judge): Builder
    {
        return $query->where('judge_id', $judge->id);
    }
}
