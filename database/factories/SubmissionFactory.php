<?php

namespace Database\Factories;

use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\Event;
use App\Models\Submission;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    protected $model = Submission::class;

    /**
     * Padrão: rascunho preenchido, ainda não enviado.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'team_id' => Team::factory(),
            'title' => Str::title(fake()->words(3, true)),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraph(),
            'repo_url' => 'https://github.com/'.fake()->userName().'/'.fake()->slug(2),
            'video_url' => null,
            'deploy_url' => null,
            'status' => SubmissionStatus::Draft,
            'source' => SubmissionSource::Web,
            'current_version' => 0,
            'submitted_at' => null,
        ];
    }

    /** Enviada dentro do prazo. */
    public function enviada(): static
    {
        return $this->state([
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now()->subHour(),
            'current_version' => 1,
        ]);
    }

    /** Enviada depois do prazo -- aceita, marcada, e o organizador decide. */
    public function atrasada(): static
    {
        return $this->state([
            'status' => SubmissionStatus::Late,
            'submitted_at' => now()->subMinutes(5),
            'current_version' => 1,
        ]);
    }

    public function desclassificada(): static
    {
        return $this->state(['status' => SubmissionStatus::Disqualified]);
    }

    /** Lançada pela organização fora do sistema -- PLANO.md, Anexo A. */
    public function manual(): static
    {
        return $this->state([
            'source' => SubmissionSource::Manual,
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
            'original_submitted_at' => now()->subHours(2),
            'current_version' => 1,
        ]);
    }
}
