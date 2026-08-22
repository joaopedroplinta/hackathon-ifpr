<?php

namespace Tests\Feature\Certificates;

use App\Enums\CertificateType;
use App\Enums\TipoVinculo;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Só o PDF (baixado pelo próprio dono, autenticado) mostra CPF e matrícula.
 * A página pública /validar/{code} nunca deve ganhar esses campos -- ver
 * CertificateValidationController e docs/ropa.md linha 52.
 */
class CertificatePdfTest extends TestCase
{
    use RefreshDatabase;

    private function render(Certificate $certificate): string
    {
        return view('certificates.pdf', ['certificate' => $certificate->load(['event', 'user'])])->render();
    }

    public function test_pdf_shows_formatted_cpf_and_matricula_for_aluno_ifpr(): void
    {
        $user = User::factory()->create([
            'cpf' => '12345678900',
            'tipo_vinculo' => TipoVinculo::AlunoIfpr,
            'matricula_suap' => '2024001',
        ]);
        $event = Event::factory()->create();
        $certificate = Certificate::factory()->for($event)->for($user)->create([
            'type' => CertificateType::Participacao,
            'payload' => ['carga_horaria' => 10, 'equipe' => 'Os Devs', 'projeto' => 'App Incrível'],
        ]);

        $html = $this->render($certificate);

        $this->assertStringContainsString('123.456.789-00', $html);
        $this->assertStringContainsString('2024001', $html);
        $this->assertStringContainsString('Os Devs', $html);
        $this->assertStringContainsString('App Incrível', $html);
    }

    public function test_pdf_shows_placeholder_when_cpf_is_missing(): void
    {
        $user = User::factory()->create(['cpf' => null]);
        $event = Event::factory()->create();
        $certificate = Certificate::factory()->for($event)->for($user)->create([
            'type' => CertificateType::Participacao,
            'payload' => ['carga_horaria' => 5],
        ]);

        $html = $this->render($certificate);

        $this->assertStringContainsString('não informado', $html);
    }

    public function test_pdf_omits_matricula_for_externo(): void
    {
        $user = User::factory()->create(['tipo_vinculo' => TipoVinculo::Externo]);
        $event = Event::factory()->create();
        $certificate = Certificate::factory()->for($event)->for($user)->create([
            'type' => CertificateType::Participacao,
            'payload' => ['carga_horaria' => 5],
        ]);

        $html = $this->render($certificate);

        $this->assertStringNotContainsString('Matrícula:', $html);
    }
}
