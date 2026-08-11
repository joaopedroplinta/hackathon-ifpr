<?php

namespace Database\Factories;

use App\Enums\JudgeAssignmentStatus;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JudgeAssignment>
 */
class JudgeAssignmentFactory extends Factory
{
    protected $model = JudgeAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'judge_id' => User::factory(),
            'submission_id' => Submission::factory(),
            'status' => JudgeAssignmentStatus::Pending,
            'assigned_at' => now(),
        ];
    }
}
