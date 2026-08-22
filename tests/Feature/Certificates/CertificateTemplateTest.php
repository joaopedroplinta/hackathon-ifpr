<?php

namespace Tests\Feature\Certificates;

use App\Actions\Certificates\IssueCertificate;
use App\Enums\CertificateType;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Personalização de logo/cor do certificado (issue #122). O design é lido
 * do snapshot em `payload.template`, nunca do Event ao vivo -- ver
 * IssueCertificate e resources/views/certificates/pdf.blade.php.
 */
class CertificateTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function render(Certificate $certificate): string
    {
        return view('certificates.pdf', ['certificate' => $certificate->load(['event', 'user'])])->render();
    }

    public function test_pdf_uses_the_default_accent_color_when_template_has_none(): void
    {
        $certificate = Certificate::factory()->for(Event::factory())->for(User::factory())->create([
            'type' => CertificateType::Participacao,
            'payload' => ['carga_horaria' => 5],
        ]);

        $html = $this->render($certificate);

        $this->assertStringContainsString('#357724', $html);
    }

    public function test_pdf_uses_the_accent_color_snapshotted_at_issuance(): void
    {
        $certificate = Certificate::factory()->for(Event::factory())->for(User::factory())->create([
            'type' => CertificateType::Participacao,
            'payload' => ['carga_horaria' => 5, 'template' => ['accent_color' => '#112233', 'logo_path' => null]],
        ]);

        $html = $this->render($certificate);

        $this->assertStringContainsString('#112233', $html);
    }

    public function test_pdf_embeds_the_logo_snapshotted_at_issuance(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('certificados/logos/logo-teste.png', 'conteudo-fake-png');

        $certificate = Certificate::factory()->for(Event::factory())->for(User::factory())->create([
            'type' => CertificateType::Participacao,
            'payload' => ['carga_horaria' => 5, 'template' => ['accent_color' => null, 'logo_path' => 'certificados/logos/logo-teste.png']],
        ]);

        $html = $this->render($certificate);

        $this->assertStringContainsString('<img class="logo"', $html);
        $this->assertStringContainsString(base64_encode('conteudo-fake-png'), $html);
    }

    public function test_issuing_a_certificate_snapshots_the_events_current_template(): void
    {
        $event = Event::factory()->create([
            'certificate_accent_color' => '#abcdef',
            'certificate_logo_path' => 'certificados/logos/logo-evento.png',
        ]);
        $user = User::factory()->create();

        $certificate = app(IssueCertificate::class)->handle($event, $user, CertificateType::Participacao);

        $this->assertSame([
            'logo_path' => 'certificados/logos/logo-evento.png',
            'accent_color' => '#abcdef',
        ], $certificate->payload['template']);
    }

    public function test_changing_the_events_template_does_not_affect_an_already_issued_certificate(): void
    {
        $event = Event::factory()->create(['certificate_accent_color' => '#111111']);
        $user = User::factory()->create();

        $certificate = app(IssueCertificate::class)->handle($event, $user, CertificateType::Participacao);

        $event->update(['certificate_accent_color' => '#222222']);

        $this->assertSame('#111111', $certificate->fresh()->payload['template']['accent_color']);
    }
}
