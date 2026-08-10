<?php

namespace App\Models;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * status, submitted_at, current_version, source, recorded_by e
     * original_submitted_at ficam de fora: quem decide se um envio está no
     * prazo é o servidor, nunca o formulário -- .claude/rules/security.md.
     */
    protected $fillable = [
        'title',
        'summary',
        'description',
        'repo_url',
        'video_url',
        'deploy_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'source' => SubmissionSource::class,
            'submitted_at' => 'datetime',
            'original_submitted_at' => 'datetime',
            'current_version' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** Histórico completo de envios, do mais recente para o mais antigo. */
    public function versions(): HasMany
    {
        return $this->hasMany(SubmissionVersion::class)->orderByDesc('version');
    }

    /** Organizador que lançou a submissão no lugar da equipe, se houve. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Já saiu do rascunho — no prazo ou fora dele. */
    public function isSubmitted(): bool
    {
        return $this->status->countsForEvaluation();
    }

    public function isDraft(): bool
    {
        return $this->status === SubmissionStatus::Draft;
    }

    /**
     * @param  Builder<Submission>  $query
     * @return Builder<Submission>
     */
    public function scopeForEvent(Builder $query, Event $event): Builder
    {
        return $query->where('event_id', $event->id);
    }
}
