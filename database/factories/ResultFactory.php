<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Result;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Result>
 */
class ResultFactory extends Factory
{
    protected $model = Result::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'submission_id' => Submission::factory(),
            'final_score' => null,
            'criteria_breakdown' => null,
            'computed_at' => now(),
        ];
    }
}
