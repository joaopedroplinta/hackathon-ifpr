<?php

namespace Tests\Feature\Console;

use App\Enums\SubmissionSource;
use App\Models\Event;
use App\Models\Submission;
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

    /**
     * Anexo A.9, item 2: "Importar CSV do Forms com 1 conflito proposital".
     * Equipe que já tem submissão que conta pra avaliação nunca é
     * sobrescrita em silêncio -- vira relatório de conflito pra decisão
     * humana (Anexo A.4).
     */
    public function test_reports_a_conflict_instead_of_overwriting_an_existing_submission(): void
    {
        $event = Event::factory()->create();
        $lider = User::factory()->create(['email' => 'lider@example.com']);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);
        Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'Título original']);

        $path = tempnam(sys_get_temp_dir(), 'ensaio-csv-');
        file_put_contents(
            $path,
            "email_lider,titulo,resumo,repo_url,video_url,enviado_em\n".
            "lider@example.com,Título do CSV,Resumo do CSV,https://github.com/x/y,,\n"
        );

        $this->artisan('hackathon:import-submissions', ['csv' => $path, 'event' => $event->slug])
            ->expectsOutputToContain('Importadas: 0')
            ->expectsOutputToContain('Conflito -- equipe já tinha submissão, nada foi sobrescrito:')
            ->expectsOutputToContain($team->name)
            ->assertExitCode(0);

        $this->assertDatabaseHas('submissions', [
            'team_id' => $team->id,
            'title' => 'Título original',
        ]);
        $this->assertDatabaseMissing('submissions', ['title' => 'Título do CSV']);
    }

    /**
     * Uma equipe em conflito não impede as demais de importarem normalmente
     * -- o relatório é por linha, o import continua.
     */
    public function test_import_continues_past_a_conflict_for_the_remaining_rows(): void
    {
        $event = Event::factory()->create();

        $liderComConflito = User::factory()->create(['email' => 'ja-enviou@example.com']);
        $equipeComConflito = Team::factory()->for($event)->create(['leader_id' => $liderComConflito->id]);
        Submission::factory()->for($event)->for($equipeComConflito)->enviada()->create();

        $liderLivre = User::factory()->create(['email' => 'ainda-nao@example.com']);
        $equipeLivre = Team::factory()->for($event)->create(['leader_id' => $liderLivre->id]);

        $path = tempnam(sys_get_temp_dir(), 'ensaio-csv-');
        file_put_contents(
            $path,
            "email_lider,titulo,resumo,repo_url,video_url,enviado_em\n".
            "ja-enviou@example.com,Tentativa duplicada,,https://github.com/x/y,,\n".
            "ainda-nao@example.com,Título novo,,https://github.com/x/z,,\n"
        );

        $this->artisan('hackathon:import-submissions', ['csv' => $path, 'event' => $event->slug])
            ->expectsOutputToContain('Importadas: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('submissions', [
            'team_id' => $equipeLivre->id,
            'title' => 'Título novo',
        ]);
    }
}
