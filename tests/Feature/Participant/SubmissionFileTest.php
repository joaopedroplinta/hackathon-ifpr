<?php

namespace Tests\Feature\Participant;

use App\Enums\Role;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SubmissionFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        // O disco 'local' do Laravel 12 é storage/app/private.
        Storage::fake('local');
    }

    private function inscrito(Event $event): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        EventRegistration::factory()->for($event)->for($user)->create();

        return $user;
    }

    /**
     * @return array{0: Team, 1: User}
     */
    private function equipeComLider(Event $event): array
    {
        $leader = $this->inscrito($event);
        $team = Team::factory()->for($event)->create(['leader_id' => $leader->id]);
        TeamMember::factory()->for($event)->for($team)->for($leader)->lider()->create();

        return [$team, $leader];
    }

    private function pdf(string $name = 'pitch.pdf', int $kilobytes = 200): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
    }

    public function test_a_member_attaches_a_pdf_and_it_lands_outside_the_webroot(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)
            ->post(route('submission-files.store'), ['arquivo' => $this->pdf()])
            ->assertRedirect(route('submissions.show'))
            ->assertSessionHas('sucesso');

        $file = SubmissionFile::firstOrFail();

        $this->assertSame('pitch.pdf', $file->original_name);
        $this->assertSame('application/pdf', $file->mime);
        $this->assertSame($leader->id, $file->uploaded_by);
        Storage::disk('local')->assertExists($file->path);
    }

    /**
     * Nome vindo do cliente nunca toca o caminho do disco.
     */
    public function test_the_stored_path_never_contains_the_original_file_name(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), [
            'arquivo' => $this->pdf('../../etc/passwd relatorio final.pdf'),
        ])->assertRedirect();

        $file = SubmissionFile::firstOrFail();

        $this->assertStringNotContainsString('relatorio', $file->path);
        $this->assertStringNotContainsString('passwd', $file->path);
        $this->assertStringNotContainsString('..', $file->path);
        $this->assertStringStartsWith("submissions/{$file->submission_id}/", $file->path);
        // O nome sobrevive só como metadado, e já chega sem os componentes de
        // caminho: getClientOriginalName() do Symfony reduz ao basename antes
        // de nós vermos. Duas defesas, não uma.
        $this->assertSame('passwd relatorio final.pdf', $file->original_name);
    }

    public function test_an_executable_renamed_to_pdf_is_refused(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), [
            'arquivo' => UploadedFile::fake()->create('malware.pdf', 10, 'application/x-msdownload'),
        ])->assertSessionHasErrors('arquivo');

        $this->assertSame(0, SubmissionFile::count());
    }

    public function test_a_file_type_outside_the_allowlist_is_refused(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), [
            'arquivo' => UploadedFile::fake()->create('planilha.xlsx', 10, 'application/vnd.ms-excel'),
        ])->assertSessionHasErrors('arquivo');

        $this->assertSame(0, SubmissionFile::count());
    }

    public function test_a_file_over_25_mb_is_refused_with_a_message_in_portuguese(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $response = $this->actingAs($leader)->post(route('submission-files.store'), [
            'arquivo' => $this->pdf('gigante.pdf', 25 * 1024 + 1),
        ]);

        $response->assertSessionHasErrors('arquivo');
        $this->assertStringContainsString(
            '25 MB',
            session('errors')->first('arquivo')
        );
        $this->assertSame(0, SubmissionFile::count());
    }

    public function test_attaching_opens_the_draft_when_the_team_has_not_written_anything_yet(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        $this->assertNull($team->submission);

        $this->actingAs($leader)->post(route('submission-files.store'), ['arquivo' => $this->pdf()])
            ->assertRedirect();

        $this->assertNotNull($team->fresh()->submission);
        $this->assertSame(1, Submission::count());
    }

    public function test_the_file_limit_per_submission_is_enforced(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        $submission = Submission::factory()->for($event)->for($team)->create();
        SubmissionFile::factory()->count(Submission::MAX_FILES)->for($submission)->create();

        $this->actingAs($leader)
            ->post(route('submission-files.store'), ['arquivo' => $this->pdf()])
            ->assertForbidden();

        $this->assertSame(Submission::MAX_FILES, SubmissionFile::count());
    }

    public function test_a_member_downloads_the_file_through_the_authorized_route(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), ['arquivo' => $this->pdf()]);

        $file = SubmissionFile::firstOrFail();

        $this->actingAs($leader)
            ->get(route('submission-files.download', $file))
            ->assertOk()
            ->assertDownload('pitch.pdf');
    }

    public function test_someone_from_another_team_cannot_download_the_file(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), ['arquivo' => $this->pdf()]);
        $file = SubmissionFile::firstOrFail();

        [, $outroLider] = $this->equipeComLider($event);

        $this->actingAs($outroLider)
            ->get(route('submission-files.download', $file))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_download_the_file(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team] = $this->equipeComLider($event);

        $submission = Submission::factory()->for($event)->for($team)->create();
        $file = SubmissionFile::factory()->for($submission)->create();

        $this->get(route('submission-files.download', $file))->assertRedirect(route('login'));
    }

    public function test_the_organizer_can_download_any_file(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), ['arquivo' => $this->pdf()]);
        $file = SubmissionFile::firstOrFail();

        $organizador = User::factory()->create(['email_verified_at' => now()]);
        $organizador->assignRole(Role::Organizador->value);

        $this->actingAs($organizador)
            ->get(route('submission-files.download', $file))
            ->assertOk();
    }

    public function test_a_member_removes_a_file_and_the_record_survives(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), ['arquivo' => $this->pdf()]);
        $file = SubmissionFile::firstOrFail();

        $this->actingAs($leader)
            ->delete(route('submission-files.destroy', $file))
            ->assertRedirect(route('submissions.show'))
            ->assertSessionHas('sucesso');

        $this->assertSame(0, SubmissionFile::count());
        // Soft delete: o registro do que foi enviado continua reconstituível.
        $this->assertSame(1, SubmissionFile::withTrashed()->count());
    }

    public function test_a_team_that_already_submitted_cannot_remove_files_after_the_deadline(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addHour()]);
        [$team, $leader] = $this->equipeComLider($event);

        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();
        $file = SubmissionFile::factory()->for($submission)->create();

        $event->update(['submission_deadline' => now()->subMinute()]);

        $this->actingAs($leader)
            ->delete(route('submission-files.destroy', $file))
            ->assertForbidden();

        $this->assertSame(1, SubmissionFile::count());
    }

    public function test_the_submitted_version_records_which_files_were_attached(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [, $leader] = $this->equipeComLider($event);

        $this->actingAs($leader)->post(route('submission-files.store'), ['arquivo' => $this->pdf('slide.pdf')]);

        $this->actingAs($leader)->post(route('submissions.submit'), [
            'title' => 'Painel de alertas',
            'summary' => 'Avisa moradores de área de risco quando o rio sobe demais.',
            'repo_url' => 'https://github.com/equipe/alerta',
        ])->assertRedirect();

        $version = Submission::firstOrFail()->versions()->firstOrFail();

        $this->assertCount(1, $version->payload['files']);
        $this->assertSame('slide.pdf', $version->payload['files'][0]['original_name']);
    }

    public function test_the_page_lists_the_attached_files(): void
    {
        $event = Event::factory()->aberto()->create(['submission_deadline' => now()->addDay()]);
        [$team, $leader] = $this->equipeComLider($event);

        $submission = Submission::factory()->for($event)->for($team)->create();
        SubmissionFile::factory()->for($submission)->create(['original_name' => 'pitch.pdf']);

        $this->actingAs($leader)
            ->get(route('submissions.show'))
            ->assertInertia(fn ($page) => $page
                ->component('submissao/minha')
                ->where('arquivos.limite', Submission::MAX_FILES)
                ->where('arquivos.itens.0.nome', 'pitch.pdf')
                ->where('arquivos.itens.0.tamanho', '512 KB')
                ->where('arquivos.itens.0.pode_remover', true)
            );
    }
}
