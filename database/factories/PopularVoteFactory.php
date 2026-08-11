<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\PopularVote;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PopularVote>
 */
class PopularVoteFactory extends Factory
{
    protected $model = PopularVote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'submission_id' => Submission::factory(),
            'user_id' => User::factory(),
        ];
    }
}
