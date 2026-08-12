<?php

namespace Tests\Feature\Public;

use App\Enums\CertificateType;
use App\Models\Certificate;
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
