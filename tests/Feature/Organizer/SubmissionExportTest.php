<?php

namespace Tests\Feature\Organizer;

use App\Enums\Role;
use App\Models\Event;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;
use ZipArchive;

class SubmissionExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // O disco 'local' do Laravel 12 é storage/app/private.
        Storage::fake('local');
    }

    private function organizador(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Organizador->value);

        return $user;
    }

    private function anexar(Submission $submission, string $nomeOriginal, string $conteudo = 'conteudo'): SubmissionFile
    {
        $path = "submissions/{$submission->id}/".Str::random(20).'.pdf';
        Storage::disk('local')->put($path, $conteudo);

        $file = new SubmissionFile;
        $file->submission_id = $submission->id;
        $file->uploaded_by = $submission->team->leader_id;
        $file->version = $submission->current_version ?: 1;
        $file->path = $path;
        $file->original_name = $nomeOriginal;
        $file->mime = 'application/pdf';
        $file->size = strlen($conteudo);
        $file->save();

        return $file;
    }

    /**
     * @return array{0: string, 1: ZipArchive}
     */
    private function abrirZip(TestResponse $response): array
    {
        /** @var BinaryFileResponse $base */
        $base = $response->baseResponse;
        $path = $base->getFile()->getPathname();
        $zip = new ZipArchive;
        $zip->open($path);

        return [$path, $zip];
    }

    public function test_staff_downloads_a_zip_with_the_csv_and_the_team_files(): void
    {
        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create(['name' => 'Equipe Alfa']);
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();
        $this->anexar($submission, 'pitch.pdf');

        $response = $this->actingAs($this->organizador())
            ->get(route('admin.submissions.export'))
            ->assertOk();

        [, $zip] = $this->abrirZip($response);

        $this->assertNotFalse($zip->locateName('submissoes.csv'));
        $this->assertNotFalse($zip->locateName('equipe-alfa/pitch.pdf'));

        $csv = $zip->getFromName('submissoes.csv');
        $this->assertStringContainsString('Equipe Alfa', $csv);
    }

    public function test_a_participant_cannot_export(): void
    {
        Event::factory()->create();

        $participante = User::factory()->create(['email_verified_at' => now()]);
        $participante->assignRole(Role::Participante->value);

        $this->actingAs($participante)
            ->get(route('admin.submissions.export'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.submissions.export'))->assertRedirect(route('login'));
    }

    /** O zip respeita o mesmo filtro da tela -- baixar "filtrado" é baixar só isso. */
    public function test_the_export_respects_the_current_filters(): void
    {
        $event = Event::factory()->create();

        $noPrazo = Team::factory()->for($event)->create(['name' => 'No Prazo']);
        Submission::factory()->for($event)->for($noPrazo)->enviada()->create();

        $atrasada = Team::factory()->for($event)->create(['name' => 'Atrasada']);
        Submission::factory()->for($event)->for($atrasada)->atrasada()->create();

        $response = $this->actingAs($this->organizador())
            ->get(route('admin.submissions.export', ['status' => 'late']))
            ->assertOk();

        [, $zip] = $this->abrirZip($response);
        $csv = $zip->getFromName('submissoes.csv');

        $this->assertStringContainsString('Atrasada', $csv);
        $this->assertStringNotContainsString('No Prazo', $csv);
    }

    public function test_the_export_never_includes_another_event_submission(): void
    {
        $atual = Event::factory()->create(['edition' => 2]);
        $anterior = Event::factory()->create(['edition' => 1]);

        $teamAtual = Team::factory()->for($atual)->create(['name' => 'Deste Ano']);
        Submission::factory()->for($atual)->for($teamAtual)->enviada()->create();

        $teamAnterior = Team::factory()->for($anterior)->create(['name' => 'Ano Passado']);
        Submission::factory()->for($anterior)->for($teamAnterior)->enviada()->create();

        $response = $this->actingAs($this->organizador())
            ->get(route('admin.submissions.export'))
            ->assertOk();

        [, $zip] = $this->abrirZip($response);
        $csv = $zip->getFromName('submissoes.csv');

        $this->assertStringContainsString('Deste Ano', $csv);
        $this->assertStringNotContainsString('Ano Passado', $csv);
    }

    /** Trilha só filtra por Track do próprio evento -- ver ListSubmissionsRequest. */
    public function test_an_invalid_status_filter_is_rejected(): void
    {
        Event::factory()->create();

        $this->actingAs($this->organizador())
            ->get(route('admin.submissions.export', ['status' => 'inventado']))
            ->assertSessionHasErrors('status');
    }
}
