<?php

namespace Database\Factories;

use App\Enums\AttendanceMethod;
use App\Models\Attendance;
use App\Models\Checkpoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'checkpoint_id' => Checkpoint::factory(),
            'user_id' => User::factory(),
            'checked_in_at' => now(),
            'checked_by' => User::factory(),
            'method' => AttendanceMethod::Manual,
        ];
    }

    public function viaQrCode(): static
    {
        return $this->state(['method' => AttendanceMethod::Qr]);
    }
}
