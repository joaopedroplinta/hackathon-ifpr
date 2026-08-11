<?php

namespace Database\Factories;

use App\Models\Criterion;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Criterion>
 */
class CriterionFactory extends Factory
{
    protected $model = Criterion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rubric_id' => Rubric::factory(),
            'name' => fake()->randomElement(['Inovação', 'Execução técnica', 'Impacto', 'Apresentação']),
            'description' => fake()->sentence(),
            'weight' => fake()->randomElement(['1.00', '1.50', '2.00']),
            'max_score' => 10,
            'position' => 0,
        ];
    }
}
