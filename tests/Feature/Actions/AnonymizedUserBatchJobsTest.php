<?php

namespace Tests\Feature\Actions;

use App\Actions\Certificates\IssueEventCertificates;
use App\Actions\Notifications\SendDeadlineReminders;
use App\Actions\Users\AnonymizeUser;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\SubmissionDeadlineReminder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Conta apagada (anonimizada) mantém inscrição e vínculo com a equipe, mas a
 * relação `user` vem nula. Os lotes não podem abortar por causa de uma pessoa.
 */
class AnonymizedUserBatchJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_issuing_skips_a_deleted_user_and_still_issues_for_the_others(): void
    {
        $this->seed(RoleSeeder::class);
        $event = Event::factory()->create();
        $apagado = User::factory()->create();
        $ficou = User::factory()->create();
        EventRegistration::factory()->for($event)->for($apagado)->create();
        EventRegistration::factory()->for($event)->for($ficou)->create();

        app(AnonymizeUser::class)->handle($apagado);

        // Antes lançava `Call to a member function attendances() on null`.
        $emitidos = app(IssueEventCertificates::class)->handle($event);

        $this->assertSame(0, $emitidos['participacao'], 'Ninguém fez check-in neste cenário.');
    }

    public function test_deadline_reminders_skip_a_deleted_member_and_mark_the_window_as_sent(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addHours(20)]);
        $team = Team::factory()->for($event)->create();
        $apagado = User::factory()->create();
        $vivo = User::factory()->create();
        TeamMember::factory()->for($event)->for($team)->for($apagado)->lider()->create();
        TeamMember::factory()->for($event)->for($team)->for($vivo)->create();

        app(AnonymizeUser::class)->handle($apagado);

        $enviados = app(SendDeadlineReminders::class)->handle($event);

        $this->assertSame(1, $enviados['enviados_24h']);
        Notification::assertSentTo($vivo, SubmissionDeadlineReminder::class);
        $this->assertNotNull($event->fresh()->reminder_24h_sent_at, 'Sem o marcador o agendador repetia a falha a cada 5 minutos.');
    }

    public function test_the_reminder_command_keeps_going_when_one_event_fails(): void
    {
        $quebrado = Event::factory()->create(['name' => 'Evento quebrado', 'submission_deadline' => now()->addHours(20)]);
        $saudavel = Event::factory()->create(['name' => 'Evento saudável', 'submission_deadline' => now()->addHours(20)]);

        $this->mock(SendDeadlineReminders::class, function ($mock) use ($quebrado, $saudavel) {
            $mock->shouldReceive('handle')->with(\Mockery::on(fn ($e) => $e->is($quebrado)))->andThrow(new \RuntimeException('falhou'));
            $mock->shouldReceive('handle')->with(\Mockery::on(fn ($e) => $e->is($saudavel)))->andReturn(['enviados_24h' => 2, 'enviados_1h' => 0]);
        });

        $this->artisan('hackathon:send-deadline-reminders')
            ->expectsOutputToContain('Evento saudável')
            ->assertSuccessful();
    }
}
