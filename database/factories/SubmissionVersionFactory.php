<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubmissionVersion>
 */
class SubmissionVersionFactory extends Factory
{
    protected $model = SubmissionVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'created_by' => User::factory(),
            'version' => 1,
            'payload' => [
                'title' => Str::title(fake()->words(3, true)),
                'summary' => fake()->sentence(12),
                'repo_url' => 'https://github.com/'.fake()->userName().'/'.fake()->slug(2),
                'submitted_at' => now()->toIso8601String(),
            ],
        ];
    }
}
