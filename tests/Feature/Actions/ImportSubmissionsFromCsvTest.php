<?php

namespace Tests\Feature\Actions;

use App\Actions\Submissions\ImportSubmissionsFromCsv;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Models\Event;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportSubmissionsFromCsvTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $conteudo): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ensaio-csv-');
        file_put_contents($path, $conteudo);

        return $path;
    }

    public function test_imports_a_row_matched_by_leader_email(): void
    {
        $event = Event::factory()->create();
        $lider = User::factory()->create(['email' => 'lider@example.com']);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);

        $csv = $this->csv(
            "email_lider,titulo,resumo,repo_url,video_url,enviado_em\n".
            "lider@example.com,App de Emergência,Um resumo qualquer,https://github.com/x/y,,2026-08-20T10:00:00-03:00\n"
        );

        $resultado = app(ImportSubmissionsFromCsv::class)->handle($event, $csv, SubmissionSource::Form);

        $this->assertSame(1, $resultado['importadas']);
        $this->assertSame([], $resultado['conflitos']);
        $this->assertSame([], $resultado['nao_encontrados']);

        $submission = Submission::forEvent($event)->where('team_id', $team->id)->firstOrFail();
        $this->assertSame('App de Emergência', $submission->title);
        $this->assertSame(SubmissionSource::Form, $submission->source);
        $this->assertNull($submission->recorded_by);
        $this->assertSame(SubmissionStatus::Submitted, $submission->status);
    }

    public function test_reports_an_email_that_matches_no_team_leader(): void
    {
        $event = Event::factory()->create();

        $csv = $this->csv(
            "email_lider,titulo,resumo,repo_url,video_url,enviado_em\n".
            "nao-existe@example.com,Título,Resumo,https://github.com/x/y,,\n"
        );

        $resultado = app(ImportSubmissionsFromCsv::class)->handle($event, $csv, SubmissionSource::Form);

        $this->assertSame(0, $resultado['importadas']);
        $this->assertSame(['nao-existe@example.com'], $resultado['nao_encontrados']);
    }

    public function test_does_not_overwrite_a_team_that_already_submitted(): void
    {
        $event = Event::factory()->create();
        $lider = User::factory()->create(['email' => 'lider@example.com']);
        $team = Team::factory()->for($event)->create(['leader_id' => $lider->id]);
        $submissaoOriginal = Submission::factory()->for($event)->for($team)->enviada()->create(['title' => 'Original']);

        $csv = $this->csv(
            "email_lider,titulo,resumo,repo_url,video_url,enviado_em\n".
            "lider@example.com,Tentativa de sobrescrever,Resumo,https://github.com/x/y,,\n"
        );

        $resultado = app(ImportSubmissionsFromCsv::class)->handle($event, $csv, SubmissionSource::Form);

        $this->assertSame(0, $resultado['importadas']);
        $this->assertSame([$team->name], $resultado['conflitos']);
        $this->assertSame('Original', $submissaoOriginal->fresh()->title);
    }

    public function test_marks_as_late_when_submitted_after_the_deadline(): void
    {
        $event = Event::factory()->create(['submission_deadline' => '2026-08-20T12:00:00-03:00']);
        $lider = User::factory()->create(['email' => 'lider@example.com']);
        Team::factory()->for($event)->create(['leader_id' => $lider->id]);

        $csv = $this->csv(
            "email_lider,titulo,resumo,repo_url,video_url,enviado_em\n".
            "lider@example.com,Título,Resumo,https://github.com/x/y,,2026-08-20T13:00:00-03:00\n"
        );

        app(ImportSubmissionsFromCsv::class)->handle($event, $csv, SubmissionSource::Form);

        $submission = Submission::forEvent($event)->firstOrFail();
        $this->assertSame(SubmissionStatus::Late, $submission->status);
    }
}
