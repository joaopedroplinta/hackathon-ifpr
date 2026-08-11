<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Rubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rubric>
 */
class RubricFactory extends Factory
{
    protected $model = Rubric::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => 'Rubrica padrão',
            'is_active' => false,
        ];
    }

    public function ativa(): static
    {
        return $this->state(['is_active' => true]);
    }
}
