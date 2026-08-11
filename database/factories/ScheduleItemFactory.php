<?php

namespace Database\Factories;

use App\Enums\ScheduleItemType;
use App\Models\Event;
use App\Models\ScheduleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleItem>
 */
class ScheduleItemFactory extends Factory
{
    protected $model = ScheduleItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicio = fake()->dateTimeBetween('+1 week', '+2 weeks');

        return [
            'event_id' => Event::factory(),
            'track_id' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'type' => fake()->randomElement(ScheduleItemType::cases()),
            'starts_at' => $inicio,
            'ends_at' => (clone $inicio)->modify('+1 hour'),
            'location' => 'Bloco '.fake()->randomLetter(),
            'is_published' => false,
        ];
    }

    public function publicado(): static
    {
        return $this->state(['is_published' => true]);
    }
}
