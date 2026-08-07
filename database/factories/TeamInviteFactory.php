<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Team;
use App\Models\TeamInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamInvite>
 */
class TeamInviteFactory extends Factory
{
    protected $model = TeamInvite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'team_id' => Team::factory(),
            'email' => fake()->unique()->safeEmail(),
            'token' => Str::random(40),
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function expirado(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function aceito(): static
    {
        return $this->state(['accepted_at' => now()]);
    }
}
