<?php

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * results_published_at e judges_per_submission ficam de fora de
     * propósito: publicar resultado e mudar quantos jurados avaliam cada
     * submissão são decisões do organizador, nunca efeito colateral de um
     * update() qualquer -- .claude/rules/security.md.
     */
    protected $fillable = [
        'name',
        'slug',
        'edition',
        'status',
        'description',
        'registration_opens_at',
        'registration_closes_at',
        'starts_at',
        'ends_at',
        'submission_deadline',
        'voting_opens_at',
        'voting_closes_at',
        'min_team_size',
        'max_team_size',
    ];

    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'submission_deadline' => 'datetime',
            'voting_opens_at' => 'datetime',
            'voting_closes_at' => 'datetime',
            'results_published_at' => 'datetime',
            'reminder_24h_sent_at' => 'datetime',
            'reminder_1h_sent_at' => 'datetime',
            'edition' => 'integer',
            'min_team_size' => 'integer',
            'max_team_size' => 'integer',
            'judges_per_submission' => 'integer',
        ];
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    public function judgeAssignments(): HasMany
    {
        return $this->hasMany(JudgeAssignment::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * O evento em foco agora. O sistema é multi-edição no banco, mas a
     * interface trabalha com um evento por vez: o mais recente que já saiu
     * do rascunho.
     */
    public static function current(): ?self
    {
        return static::query()
            ->public()
            ->orderByDesc('edition')
            // Desempate por id: sem isto, duas edições com o mesmo número
            // devolvem um evento arbitrário, e partes diferentes do sistema
            // podem resolver "o evento atual" de formas diferentes.
            ->orderByDesc('id')
            ->first();
    }

    public function isRegistered(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->registrations()->where('user_id', $user->id)->exists();
    }

    /**
     * Janela aberta = agora está entre as duas pontas.
     * Ponta nula significa "sem limite deste lado".
     */
    protected function windowIsOpen(?\DateTimeInterface $from, ?\DateTimeInterface $to): bool
    {
        $now = now();

        return ! ($from && $now->lt($from)) && ! ($to && $now->gt($to));
    }

    public function registrationIsOpen(): bool
    {
        return $this->windowIsOpen($this->registration_opens_at, $this->registration_closes_at);
    }

    /**
     * Votação é opt-in: sem período configurado, está fechada. Diferente da
     * inscrição, onde ponta nula significa "sem limite deste lado" — aqui,
     * abrir votação por esquecimento de preencher data seria pior que fechar.
     */
    public function votingIsOpen(): bool
    {
        if ($this->voting_opens_at === null) {
            return false;
        }

        return $this->windowIsOpen($this->voting_opens_at, $this->voting_closes_at);
    }

    /**
     * O prazo que vale de fato agora. Hoje é só a coluna, mas quando os
     * incidentes entrarem (PLANO.md, Anexo A.3) a extensão vale para todas
     * as equipes e será somada aqui -- em um lugar só, não espalhada por
     * cada controller que precisa saber se o prazo virou.
     */
    public function effectiveSubmissionDeadline(): ?Carbon
    {
        return $this->submission_deadline;
    }

    /**
     * Prazo de submissão comparado com now() do servidor. Data vinda do
     * cliente nunca decide isto -- .claude/rules/security.md.
     */
    public function submissionIsOpen(): bool
    {
        $deadline = $this->effectiveSubmissionDeadline();

        return $deadline === null || now()->lte($deadline);
    }

    public function resultsArePublished(): bool
    {
        return $this->results_published_at !== null;
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', '!=', EventStatus::Draft);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
