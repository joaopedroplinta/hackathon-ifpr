<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    protected $model = Track::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => fake()->unique()->randomElement([
                'Educação', 'Saúde', 'Cidade Inteligente', 'Sustentabilidade', 'Acessibilidade',
            ]),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
        ];
    }
}
