<?php

namespace Database\Factories;

use App\Enums\IncidentKind;
use App\Models\Event;
use App\Models\Incident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'kind' => IncidentKind::Rede,
            'started_at' => now(),
            'ended_at' => null,
            'description' => fake()->sentence(),
            'deadline_extension_minutes' => 0,
            'declared_by' => User::factory(),
        ];
    }
}
