<?php

namespace Database\Factories;

use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'role' => TeamMemberRole::Member,
            'status' => TeamMemberStatus::Active,
            'joined_at' => now(),
        ];
    }

    public function lider(): static
    {
        return $this->state(['role' => TeamMemberRole::Leader]);
    }

    public function saiu(): static
    {
        return $this->state([
            'status' => TeamMemberStatus::Left,
            'left_at' => now(),
        ]);
    }
}
