<?php

namespace Database\Factories;

use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'type' => CertificateType::Participacao,
            'code' => (string) Str::uuid(),
            'payload' => ['carga_horaria' => 0],
            'path' => null,
            'issued_at' => now(),
        ];
    }

    public function pronto(): static
    {
        return $this->state(fn () => ['path' => 'certificates/fake/'.Str::uuid().'.pdf']);
    }
}
