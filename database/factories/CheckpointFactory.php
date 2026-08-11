<?php

namespace Database\Factories;

use App\Enums\CheckpointType;
use App\Models\Checkpoint;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checkpoint>
 */
class CheckpointFactory extends Factory
{
    protected $model = Checkpoint::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => 'Entrada',
            'type' => CheckpointType::Entrada,
            'starts_at' => now(),
            'ends_at' => now()->addHours(2),
        ];
    }
}
