<?php

namespace Tests\Feature\Participant;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_lists_their_own_certificates(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $event = Event::factory()->create();
        Certificate::factory()->for($event)->for($user)->pronto()->create();
        Certificate::factory()->for($event)->for(User::factory()->create())->create();

        $response = $this->actingAs($user)->get(route('certificates.index'));

        $response->assertInertia(fn ($page) => $page->has('certificados', 1));
    }

    public function test_a_user_downloads_their_own_ready_certificate(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $certificate = Certificate::factory()
            ->for(Event::factory()->create())
            ->for($user)
            ->create(['path' => 'certificates/1/fake.pdf']);
        Storage::disk('local')->put($certificate->path, '%PDF-1.4 fake');

        $this->actingAs($user)
            ->get(route('certificates.download', $certificate))
            ->assertOk();
    }

    /**
     * Production: the worker marks the certificate ready but writes the PDF
     * to its own disk, which the web service cannot see (issue #222).
     */
    public function test_a_ready_certificate_missing_from_this_disk_is_rendered_on_download(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['email_verified_at' => now()]);
        $certificate = Certificate::factory()
            ->for(Event::factory()->create())
            ->for($user)
            ->create(['path' => 'certificates/1/rendered-by-the-worker.pdf']);

        $response = $this->actingAs($user)
            ->get(route('certificates.download', $certificate))
            ->assertOk()
            ->assertDownload("certificado-{$certificate->type->value}.pdf");

        $this->assertStringStartsWith('%PDF', $response->streamedContent());
        Storage::disk('local')->assertExists("certificates/{$certificate->event_id}/{$certificate->code}.pdf");
    }

    public function test_a_user_cannot_download_someone_elses_certificate(): void
    {
        $dono = User::factory()->create(['email_verified_at' => now()]);
        $outro = User::factory()->create(['email_verified_at' => now()]);
        $certificate = Certificate::factory()
            ->for(Event::factory()->create())
            ->for($dono)
            ->pronto()
            ->create();

        $this->actingAs($outro)
            ->get(route('certificates.download', $certificate))
            ->assertForbidden();
    }

    public function test_downloading_before_the_pdf_is_ready_returns_not_found(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $certificate = Certificate::factory()
            ->for(Event::factory()->create())
            ->for($user)
            ->create(['path' => null]);

        $this->actingAs($user)
            ->get(route('certificates.download', $certificate))
            ->assertNotFound();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('certificates.index'))->assertRedirect(route('login'));
    }
}
