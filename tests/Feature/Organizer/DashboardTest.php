<?php

namespace Tests\Feature\Organizer;

use App\Enums\AttendanceMethod;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Models\Attendance;
use App\Models\Checkpoint;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function organizador(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Organizador->value);

        return $user;
    }

    private function participante(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(Role::Participante->value);

        return $user;
    }

    public function test_staff_sees_the_overview_numbers(): void
    {
        $event = Event::factory()->create();

        EventRegistration::factory()->for($event)->for(User::factory())->create();
        EventRegistration::factory()->for($event)->for(User::factory())->create();

        Team::factory()->for($event)->create();
        $equipeComSubmissao = Team::factory()->for($event)->create();
        Submission::factory()->for($event)->for($equipeComSubmissao)->enviada()->create();

        $equipeAvaliada = Team::factory()->for($event)->create();
        $submissaoAvaliada = Submission::factory()->for($event)->for($equipeAvaliada)->enviada()->create();
        $assignment = new JudgeAssignment;
        $assignment->event_id = $event->id;
        $assignment->judge_id = $this->participante()->id;
        $assignment->submission_id = $submissaoAvaliada->id;
        $assignment->status = JudgeAssignmentStatus::Pending;
        $assignment->assigned_at = now();
        $assignment->save();

        $checkpoint = Checkpoint::factory()->for($event)->create();
        $attendance = new Attendance;
        $attendance->checkpoint_id = $checkpoint->id;
        $attendance->user_id = $this->participante()->id;
        $attendance->checked_in_at = now();
        $attendance->method = AttendanceMethod::Manual;
        $attendance->save();

        $response = $this->actingAs($this->organizador())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('inscritos', 2)
            ->where('equipes_sem_submissao', 1)
            ->where('atribuicoes_em_aberto', 1)
            ->where('presenca_hoje', 1)
        );
    }

    public function test_a_participant_cannot_access_the_panel(): void
    {
        Event::factory()->create();

        $this->actingAs($this->participante())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        Event::factory()->create();

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }
}
