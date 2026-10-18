<?php

namespace Tests\Feature\Participant;

use App\Actions\Results\ComputeResults;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quando o último integrante sai a equipe é dissolvida (soft delete). A
 * submissão tem que ir junto: sozinha ela ficava com `team` nulo e derrubava
 * a vitrine pública, o painel e o cálculo do resultado.
 */
class DissolvedTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_last_member_leaving_takes_the_submission_with_the_team_and_nothing_breaks(): void
    {
        $this->seed(RoleSeeder::class);
        $event = Event::factory()->aberto()->create();

        $leader = User::factory()->create(['email_verified_at' => now()]);
        $leader->assignRole(Role::Participante->value);
        $team = Team::factory()->for($event)->create(['leader_id' => $leader->id]);
        $membership = TeamMember::factory()->for($event)->for($team)->for($leader)->lider()->create();
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $other = Team::factory()->for($event)->create();
        Submission::factory()->for($event)->for($other)->enviada()->create();

        $this->actingAs($leader)->delete(route('teams.leave', $membership))->assertRedirect();

        $this->assertTrue($team->fresh() === null || Team::withTrashed()->find($team->id)->trashed());
        $this->assertTrue(Submission::withTrashed()->find($submission->id)->trashed(), 'A submissão ficou órfã.');

        auth()->logout();
        $this->get(route('projetos.index'))->assertOk();
        app(ComputeResults::class)->handle($event);
        $this->assertTrue(true, 'ComputeResults não pode lançar com a equipe dissolvida.');
    }

    public function test_a_member_leaving_a_team_that_still_has_people_keeps_the_submission(): void
    {
        $this->seed(RoleSeeder::class);
        $event = Event::factory()->aberto()->create();

        $leader = User::factory()->create(['email_verified_at' => now()]);
        $leader->assignRole(Role::Participante->value);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $member->assignRole(Role::Participante->value);
        $team = Team::factory()->for($event)->create(['leader_id' => $leader->id]);
        TeamMember::factory()->for($event)->for($team)->for($leader)->lider()->create();
        $membership = TeamMember::factory()->for($event)->for($team)->for($member)->create();
        $submission = Submission::factory()->for($event)->for($team)->enviada()->create();

        $this->actingAs($member)->delete(route('teams.leave', $membership))->assertRedirect();

        $this->assertNull(Submission::withTrashed()->find($submission->id)->deleted_at);
    }
}
