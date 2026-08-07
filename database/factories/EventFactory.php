<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Padrão: evento publicado com tudo aberto. Cada state fecha uma janela.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = '1º Hackathon IFPR Pinhais';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 9999),
            'edition' => 1,
            'status' => EventStatus::Published,
            'description' => fake()->paragraph(),
            'registration_opens_at' => now()->subWeek(),
            'registration_closes_at' => now()->addWeek(),
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addDays(2),
            'submission_deadline' => now()->addWeeks(2)->addDays(2),
            'voting_opens_at' => null,
            'voting_closes_at' => null,
            'results_published_at' => null,
            'min_team_size' => 2,
            'max_team_size' => 5,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => EventStatus::Draft]);
    }

    /** Inscrições abertas agora. */
    public function aberto(): static
    {
        return $this->state([
            'registration_opens_at' => now()->subDay(),
            'registration_closes_at' => now()->addDay(),
        ]);
    }

    public function inscricoesFechadas(): static
    {
        return $this->state([
            'registration_opens_at' => now()->subWeeks(2),
            'registration_closes_at' => now()->subDay(),
        ]);
    }

    public function inscricoesNaoAbertas(): static
    {
        return $this->state([
            'registration_opens_at' => now()->addDay(),
            'registration_closes_at' => now()->addWeek(),
        ]);
    }

    public function prazoVencido(): static
    {
        return $this->state([
            'status' => EventStatus::Running,
            'submission_deadline' => now()->subHour(),
        ]);
    }

    public function votacaoAberta(): static
    {
        return $this->state([
            'voting_opens_at' => now()->subHour(),
            'voting_closes_at' => now()->addHour(),
        ]);
    }

    public function resultadosPublicados(): static
    {
        return $this->state([
            'status' => EventStatus::Finished,
            'results_published_at' => now()->subMinute(),
        ]);
    }
}
