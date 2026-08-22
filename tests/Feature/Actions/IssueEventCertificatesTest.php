<?php

namespace Tests\Feature\Actions;

use App\Actions\Certificates\IssueEventCertificates;
use App\Enums\CertificateType;
use App\Enums\EvaluationStatus;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Jobs\GenerateCertificatePdf;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Checkpoint;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IssueEventCertificatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function inscritoComPresenca(Event $event): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);
        EventRegistration::factory()->for($event)->for($user)->create();

        $checkpoint = Checkpoint::factory()->for($event)->create();
        Attendance::factory()->for($checkpoint)->for($user)->create();

        return $user;
    }

    public function test_issues_participation_certificate_only_for_users_with_attendance(): void
    {
        Queue::fake();

        $event = Event::factory()->create();
        $comPresenca = $this->inscritoComPresenca($event);

        $semPresenca = User::factory()->create(['email_verified_at' => now()]);
        $semPresenca->assignRole(Role::Participante->value);
        EventRegistration::factory()->for($event)->for($semPresenca)->create();

        $emitidos = app(IssueEventCertificates::class)->handle($event);

        $this->assertSame(1, $emitidos['participacao']);
        $this->assertDatabaseHas('certificates', [
            'event_id' => $event->id,
            'user_id' => $comPresenca->id,
            'type' => CertificateType::Participacao->value,
        ]);
        $this->assertDatabaseMissing('certificates', [
            'event_id' => $event->id,
            'user_id' => $semPresenca->id,
        ]);

        Queue::assertPushed(GenerateCertificatePdf::class);
    }

    public function test_participation_certificate_includes_team_and_project(): void
    {
        Queue::fake();

        $event = Event::factory()->create();
        $user = $this->inscritoComPresenca($event);

        $team = Team::factory()->for($event)->create(['name' => 'Os Devs']);
        Submission::factory()->for($event)->for($team)->create(['title' => 'App Incrível']);
        TeamMember::factory()->for($event)->for($team)->for($user)->create();

        app(IssueEventCertificates::class)->handle($event);

        $certificate = Certificate::forEvent($event)
            ->where('user_id', $user->id)
            ->where('type', CertificateType::Participacao->value)
            ->first();

        $this->assertSame('Os Devs', $certificate->payload['equipe']);
        $this->assertSame('App Incrível', $certificate->payload['projeto']);
    }

    public function test_participation_certificate_without_team_has_null_equipe(): void
    {
        Queue::fake();

        $event = Event::factory()->create();
        $user = $this->inscritoComPresenca($event);

        app(IssueEventCertificates::class)->handle($event);

        $certificate = Certificate::forEvent($event)
            ->where('user_id', $user->id)
            ->where('type', CertificateType::Participacao->value)
            ->first();

        $this->assertNull($certificate->payload['equipe']);
        $this->assertNull($certificate->payload['projeto']);
    }

    public function test_issues_judge_certificate_only_for_judges_who_submitted_an_evaluation(): void
    {
        Queue::fake();

        $event = Event::factory()->create();
        $team = Team::factory()->for($event)->create();
        $submissao = Submission::factory()->for($event)->for($team)->enviada()->create();

        $jurado = User::factory()->create(['email_verified_at' => now()]);
        $jurado->assignRole(Role::Jurado->value);
        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $jurado->id;
        $assignment->submission_id = $submissao->id;
        $assignment->status = JudgeAssignmentStatus::Done;
        $assignment->assigned_at = now();
        $assignment->save();

        $evaluation = new Evaluation;
        $evaluation->assignment_id = $assignment->id;
        $evaluation->status = EvaluationStatus::Submitted;
        $evaluation->submitted_at = now();
        $evaluation->save();

        $juradoAusente = User::factory()->create(['email_verified_at' => now()]);
        $juradoAusente->assignRole(Role::Jurado->value);

        $emitidos = app(IssueEventCertificates::class)->handle($event);

        $this->assertSame(1, $emitidos['jurado']);
        $this->assertDatabaseHas('certificates', [
            'event_id' => $event->id,
            'user_id' => $jurado->id,
            'type' => CertificateType::Jurado->value,
        ]);
        $this->assertDatabaseMissing('certificates', [
            'event_id' => $event->id,
            'user_id' => $juradoAusente->id,
        ]);
    }

    public function test_running_twice_does_not_duplicate_certificates(): void
    {
        Queue::fake();

        $event = Event::factory()->create();
        $this->inscritoComPresenca($event);

        app(IssueEventCertificates::class)->handle($event);
        app(IssueEventCertificates::class)->handle($event);

        $this->assertSame(1, Certificate::forEvent($event)->where('type', CertificateType::Participacao->value)->count());
    }

    public function test_skips_colocacao_before_results_are_published(): void
    {
        Queue::fake();

        $event = Event::factory()->create();

        $emitidos = app(IssueEventCertificates::class)->handle($event);

        $this->assertSame(0, $emitidos['colocacao']);
        $this->assertDatabaseMissing('certificates', ['type' => CertificateType::Colocacao->value]);
    }
}
