<?php

namespace Tests\Feature\Organizer;

use App\Actions\Certificates\IssueEventCertificates;
use App\Actions\Results\ComputeResults;
use App\Enums\CertificateType;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Enums\TeamStatus;
use App\Models\Certificate;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\JudgeAssignment;
use App\Models\Rubric;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Colocação concedida por um resultado que deixou de valer (equipe
 * desclassificada depois da emissão) não pode seguir validando em público.
 */
class CertificateRevocationTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private User $leader;

    private Team $team;

    private Certificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Storage::fake('local');
        $this->seed(RoleSeeder::class);

        $this->event = Event::factory()->create();
        $rubric = Rubric::factory()->for($this->event)->ativa()->create();
        $criterion = Criterion::factory()->for($rubric)->create(['weight' => '1.00', 'max_score' => 10, 'position' => 0]);

        $this->leader = User::factory()->create(['email_verified_at' => now()]);
        $this->leader->assignRole(Role::Participante->value);
        EventRegistration::factory()->for($this->event)->for($this->leader)->create();
        $this->team = Team::factory()->for($this->event)->create(['status' => TeamStatus::Confirmed, 'leader_id' => $this->leader->id]);
        TeamMember::factory()->for($this->event)->for($this->team)->for($this->leader)->lider()->create();
        $submission = Submission::factory()->for($this->event)->for($this->team)->enviada()->create();

        $judge = User::factory()->create(['email_verified_at' => now()]);
        $judge->assignRole(Role::Jurado->value);
        $assignment = new JudgeAssignment;
        $assignment->event_id = $this->event->id;
        $assignment->judge_id = $judge->id;
        $assignment->submission_id = $submission->id;
        $assignment->status = JudgeAssignmentStatus::Done;
        $assignment->assigned_at = now();
        $assignment->save();
        $evaluation = new Evaluation;
        $evaluation->assignment_id = $assignment->id;
        $evaluation->status = EvaluationStatus::Submitted;
        $evaluation->submitted_at = now();
        $evaluation->save();
        $score = new EvaluationScore;
        $score->evaluation_id = $evaluation->id;
        $score->criterion_id = $criterion->id;
        $score->score = 9;
        $score->save();

        app(ComputeResults::class)->handle($this->event);
        $this->event->results_published_at = now();
        $this->event->save();

        app(IssueEventCertificates::class)->handle($this->event);
        $this->certificate = Certificate::where('type', CertificateType::Colocacao->value)->firstOrFail();
        $this->certificate->path = 'certificates/x.pdf';
        $this->certificate->save();
        Storage::disk('local')->put('certificates/x.pdf', 'pdf');
    }

    private function disqualify(): void
    {
        $staff = User::factory()->create(['email_verified_at' => now()]);
        $staff->assignRole(Role::Organizador->value);

        $this->actingAs($staff)
            ->post(route('painel.teams.disqualify', $this->team), ['motivo' => str_repeat('plágio comprovado. ', 3)])
            ->assertSessionHasNoErrors();
        auth()->logout();
    }

    public function test_the_placement_certificate_is_valid_before_the_disqualification(): void
    {
        $this->get(route('certificates.validate', $this->certificate->code))
            ->assertInertia(fn ($page) => $page->where('encontrado', true));
        $this->actingAs($this->leader)->get(route('certificates.download', $this->certificate))->assertOk();
    }

    public function test_disqualifying_the_team_revokes_its_placement_certificate(): void
    {
        $this->disqualify();

        $this->assertNotNull($this->certificate->fresh()->revoked_at);

        $this->get(route('certificates.validate', $this->certificate->code))
            ->assertInertia(fn ($page) => $page
                ->where('encontrado', false)
                ->where('revogado', true)
                ->missing('nome'));
    }

    public function test_a_revoked_certificate_cannot_be_downloaded_or_listed(): void
    {
        $this->disqualify();

        $this->actingAs($this->leader)->get(route('certificates.download', $this->certificate))->assertNotFound();
        $this->actingAs($this->leader)->get(route('certificates.index'))
            ->assertInertia(fn ($page) => $page->where('certificados', []));
    }

    public function test_reinstating_the_team_and_reissuing_makes_the_certificate_valid_again(): void
    {
        $this->disqualify();
        $this->assertNotNull($this->certificate->fresh()->revoked_at);

        $staff = User::factory()->create(['email_verified_at' => now()]);
        $staff->assignRole(Role::Organizador->value);
        $this->actingAs($staff)
            ->post(route('painel.teams.reinstate', $this->team), ['motivo' => 'Decisão revista pela comissão.'])
            ->assertSessionHasNoErrors();

        app(IssueEventCertificates::class)->handle($this->event);

        $this->assertNull($this->certificate->fresh()->revoked_at);
    }
}
