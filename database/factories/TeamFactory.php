<?php

namespace Database\Factories;

use App\Enums\TeamStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'event_id' => Event::factory(),
            'leader_id' => User::factory(),
            'track_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'invite_code' => Str::upper(Str::random(6)),
            'description' => fake()->sentence(),
            'status' => TeamStatus::Draft,
        ];
    }

    public function confirmada(): static
    {
        return $this->state([
            'status' => TeamStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function desclassificada(): static
    {
        return $this->state(['status' => TeamStatus::Disqualified]);
    }
}
