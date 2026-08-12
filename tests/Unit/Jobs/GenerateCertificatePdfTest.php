<?php

namespace Tests\Unit\Jobs;

use App\Jobs\GenerateCertificatePdf;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateCertificatePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_the_pdf_and_stores_the_path(): void
    {
        Storage::fake('local');

        $certificate = Certificate::factory()
            ->for(Event::factory()->create())
            ->for(User::factory()->create())
            ->create();

        (new GenerateCertificatePdf($certificate->id))->handle();

        $certificate->refresh();

        $this->assertNotNull($certificate->path);
        Storage::disk('local')->assertExists($certificate->path);
    }
}
