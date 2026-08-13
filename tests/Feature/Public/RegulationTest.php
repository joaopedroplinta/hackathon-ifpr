<?php

namespace Tests\Feature\Public;

use App\Actions\Events\UploadRegulation;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // O disco 'local' do Laravel 12 é storage/app/private.
        Storage::fake('local');
    }

    public function test_the_page_renders_without_a_regulation_file(): void
    {
        Event::factory()->create(['min_team_size' => 2, 'max_team_size' => 5]);

        $this->get(route('regulamento.show'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('publico/regulamento')
                    ->where('regulamento.tem_arquivo', false)
            );
    }

    public function test_the_page_still_renders_with_no_event_at_all(): void
    {
        $this->get(route('regulamento.show'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('evento', null)->where('regulamento.tem_arquivo', false));
    }

    public function test_the_page_shows_the_download_button_once_a_file_exists(): void
    {
        $event = Event::factory()->create();
        app(UploadRegulation::class)->handle($event, UploadedFile::fake()->create('edital.pdf', 100, 'application/pdf'));

        $this->get(route('regulamento.show'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->where('regulamento.tem_arquivo', true)
                    ->where('regulamento.nome_arquivo', 'edital.pdf')
            );
    }

    public function test_download_returns_404_without_a_file(): void
    {
        Event::factory()->create();

        $this->get(route('regulamento.download'))->assertNotFound();
    }

    public function test_download_returns_404_without_any_event(): void
    {
        $this->get(route('regulamento.download'))->assertNotFound();
    }

    public function test_download_streams_the_file_once_uploaded(): void
    {
        $event = Event::factory()->create();
        app(UploadRegulation::class)->handle($event, UploadedFile::fake()->create('edital.pdf', 100, 'application/pdf'));

        $this->get(route('regulamento.download'))->assertOk();
    }
}
