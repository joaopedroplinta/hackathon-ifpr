<?php

namespace Database\Factories;

use App\Models\ConflictOfInterest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConflictOfInterest>
 */
class ConflictOfInterestFactory extends Factory
{
    protected $model = ConflictOfInterest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judge_id' => User::factory(),
            'team_id' => Team::factory(),
            'reason' => 'Orientador da equipe',
        ];
    }
}
