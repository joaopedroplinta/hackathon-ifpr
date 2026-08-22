<?php

namespace Tests\Feature\Certificates;

use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Assinatura é opcional por evento (Event::certificate_signer_name/role,
 * editada em admin/evento/editar.tsx) -- sem ela preenchida, o certificado
 * nunca inventa um nome pra assinar.
 */
class CertificateSignatureTest extends TestCase
{
    use RefreshDatabase;

    private function render(Certificate $certificate): string
    {
        return view('certificates.pdf', ['certificate' => $certificate->load(['event', 'user'])])->render();
    }

    public function test_pdf_shows_signature_block_when_event_has_a_signer(): void
    {
        $event = Event::factory()->create([
            'certificate_signer_name' => 'Maria Souza',
            'certificate_signer_role' => 'Coordenadora do Hackathon',
        ]);
        $certificate = Certificate::factory()->for($event)->for(User::factory())->create([
            'type' => CertificateType::Participacao,
        ]);

        $html = $this->render($certificate);

        $this->assertStringContainsString('Maria Souza', $html);
        $this->assertStringContainsString('Coordenadora do Hackathon', $html);
    }

    public function test_pdf_omits_signature_block_when_event_has_no_signer(): void
    {
        $event = Event::factory()->create([
            'certificate_signer_name' => null,
            'certificate_signer_role' => null,
        ]);
        $certificate = Certificate::factory()->for($event)->for(User::factory())->create([
            'type' => CertificateType::Participacao,
        ]);

        $html = $this->render($certificate);

        $this->assertStringNotContainsString('class="assinatura"', $html);
    }
}
