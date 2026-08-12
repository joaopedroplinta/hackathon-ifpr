<?php

namespace Tests\Feature\Console;

use App\Enums\SubmissionSource;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportSubmissionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_submissions_for_the_event_resolved_by_slug(): void
    {
        $event = Event::factory()->create();
        $lider = User::factory()->create(['email' => 'lider@example.com']);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);

        $path = tempnam(sys_get_temp_dir(), 'ensaio-csv-');
        file_put_contents(
            $path,
            "email_lider,titulo,resumo,repo_url,video_url,enviado_em\n".
            "lider@example.com,Título,Resumo,https://github.com/x/y,,\n"
        );

        $this->artisan('hackathon:import-submissions', ['csv' => $path, 'event' => $event->slug])
            ->assertExitCode(0);

        $this->assertDatabaseHas('submissions', [
            'team_id' => $team->id,
            'source' => SubmissionSource::Form->value,
        ]);
    }

    public function test_fails_gracefully_for_a_missing_file(): void
    {
        $event = Event::factory()->create();

        $this->artisan('hackathon:import-submissions', ['csv' => '/tmp/nao-existe.csv', 'event' => $event->slug])
            ->assertExitCode(1);
    }

    public function test_fails_gracefully_for_an_unknown_slug(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ensaio-csv-');
        file_put_contents($path, "email_lider\n");

        $this->artisan('hackathon:import-submissions', ['csv' => $path, 'event' => 'nao-existe'])
            ->assertExitCode(1);
    }

    public function test_rejects_an_invalid_source_option(): void
    {
        $event = Event::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'ensaio-csv-');
        file_put_contents($path, "email_lider\n");

        $this->artisan('hackathon:import-submissions', ['csv' => $path, 'event' => $event->slug, '--source' => 'web'])
            ->assertExitCode(1);
    }
}
