<?php

namespace Tests\Feature\Public;

use App\Enums\CertificateType;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Checkpoint;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_certificate_data_for_a_valid_code(): void
    {
        $event = Event::factory()->create(['name' => '1º Hackathon IFPR']);
        $user = User::factory()->create(['name' => 'Ana Souza']);
        $certificate = Certificate::factory()
            ->for($event)
            ->for($user)
            ->create(['type' => CertificateType::Organizador]);

        $response = $this->get(route('certificates.validate', $certificate->code));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('encontrado', true)
            ->where('nome', 'Ana Souza')
            ->where('evento', '1º Hackathon IFPR')
        );
    }

    /**
     * Achado no ensaio geral: o formulário rápido de criar checkpoint
     * (admin/checkin) só pede nome e tipo -- checkpoint sem starts_at/ends_at
     * é o caso comum, não a exceção. AttendanceHours::forUser() quebrava a
     * página inteira de validação com "Call to a member function
     * diffInMinutes() on null" pra qualquer pessoa que tivesse feito check-in
     * num checkpoint desses.
     */
    public function test_validation_never_crashes_when_the_checkpoint_has_no_time_window(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create(['name' => 'Ana Souza']);
        $checkpoint = Checkpoint::factory()->for($event)->create(['starts_at' => null, 'ends_at' => null]);
        Attendance::factory()->for($checkpoint)->for($user)->create();

        $certificate = Certificate::factory()
            ->for($event)
            ->for($user)
            ->create(['type' => CertificateType::Participacao]);

        $response = $this->get(route('certificates.validate', $certificate->code));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('encontrado', true)
            ->where('carga_horaria', 0)
        );
    }

    public function test_shows_not_found_state_for_a_valid_uuid_that_does_not_exist(): void
    {
        $response = $this->get(route('certificates.validate', '11111111-1111-1111-1111-111111111111'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('encontrado', false));
    }

    public function test_a_malformed_code_returns_a_clean_404_instead_of_a_database_error(): void
    {
        $this->get('/validar/nao-e-um-uuid')->assertNotFound();
    }
}
