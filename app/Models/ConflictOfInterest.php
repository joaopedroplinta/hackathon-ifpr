<?php

namespace App\Models;

use App\Enums\TeamMemberStatus;
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

    /**
     * O jurado não pode avaliar a equipe quando há conflito registrado ou
     * quando ele próprio é membro ativo dela -- o segundo caso não depende de
     * alguém lembrar de registrar (jurado promovido que seguia na equipe).
     */
    public static function blocks(int $judgeId, int $teamId): bool
    {
        return static::query()->where('judge_id', $judgeId)->where('team_id', $teamId)->exists()
            || TeamMember::query()
                ->where('user_id', $judgeId)
                ->where('team_id', $teamId)
                ->where('status', TeamMemberStatus::Active)
                ->exists();
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
