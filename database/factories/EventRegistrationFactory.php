<?php

namespace Database\Factories;

use App\Enums\ShirtSize;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventRegistration>
 */
class EventRegistrationFactory extends Factory
{
    protected $model = EventRegistration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'registered_at' => now(),
            'shirt_size' => fake()->randomElement(ShirtSize::cases()),
            'dietary_notes' => null,
            'phone' => fake()->numerify('(41) 9####-####'),
            'course' => 'Análise e Desenvolvimento de Sistemas',
        ];
    }
}
