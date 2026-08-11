<?php

namespace Database\Factories;

use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    protected $model = Evaluation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => JudgeAssignment::factory(),
            'status' => EvaluationStatus::Draft,
        ];
    }
}
