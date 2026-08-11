<?php

namespace Database\Factories;

use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationScore>
 */
class EvaluationScoreFactory extends Factory
{
    protected $model = EvaluationScore::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'criterion_id' => Criterion::factory(),
            'score' => $this->faker->randomFloat(2, 0, 10),
        ];
    }
}
