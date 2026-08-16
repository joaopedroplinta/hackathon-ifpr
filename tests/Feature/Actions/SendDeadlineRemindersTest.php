<?php

namespace Tests\Feature\Actions;

use App\Actions\Notifications\SendDeadlineReminders;
use App\Models\Event;
use App\Models\Incident;
use App\Models\Submission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Notifications\SubmissionDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendDeadlineRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function equipeSemSubmissao(Event $event): TeamMember
    {
        $team = Team::factory()->for($event)->create();

        return TeamMember::factory()->for($event)->for($team)->lider()->create();
    }

    public function test_sends_the_24h_reminder_once_the_window_opens(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addHours(20)]);
        $membro = $this->equipeSemSubmissao($event);

        $enviados = app(SendDeadlineReminders::class)->handle($event);

        $this->assertSame(1, $enviados['enviados_24h']);
        Notification::assertSentTo($membro->user, SubmissionDeadlineReminder::class);
        $this->assertNotNull($event->fresh()->reminder_24h_sent_at);
    }

    public function test_does_not_send_before_the_24h_window(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addDays(3)]);
        $this->equipeSemSubmissao($event);

        $enviados = app(SendDeadlineReminders::class)->handle($event);

        $this->assertSame(0, $enviados['enviados_24h']);
        Notification::assertNothingSent();
    }

    public function test_running_twice_does_not_duplicate_the_reminder(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addHours(20)]);
        $this->equipeSemSubmissao($event);

        app(SendDeadlineReminders::class)->handle($event);
        $enviados = app(SendDeadlineReminders::class)->handle($event->fresh());

        $this->assertSame(0, $enviados['enviados_24h']);
        Notification::assertSentTimes(SubmissionDeadlineReminder::class, 1);
    }

    public function test_skips_teams_that_already_submitted(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addHours(20)]);
        $team = Team::factory()->for($event)->create();
        $membro = TeamMember::factory()->for($event)->for($team)->lider()->create();
        Submission::factory()->for($event)->for($team)->enviada()->create();

        app(SendDeadlineReminders::class)->handle($event);

        Notification::assertNotSentTo($membro->user, SubmissionDeadlineReminder::class);
    }

    public function test_sends_both_reminders_when_deadline_is_very_close(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addMinutes(30)]);
        $this->equipeSemSubmissao($event);

        $enviados = app(SendDeadlineReminders::class)->handle($event);

        $this->assertSame(1, $enviados['enviados_24h']);
        $this->assertSame(1, $enviados['enviados_1h']);
    }

    /**
     * Anexo A.3 -- incidente estende o prazo pra todo mundo. O lembrete
     * precisa seguir o prazo efetivo, não a coluna crua: sem isso, o "falta
     * 1h" dispara cedo demais pro prazo antigo e nunca mais dispara pro
     * prazo de verdade, porque a coluna de dedupe já foi marcada.
     */
    public function test_follows_the_extended_deadline_after_an_incident(): void
    {
        Notification::fake();

        // Prazo original em 30 minutos: sem a extensão, isso já cairia na
        // janela de 1h e disparava agora, com o horário errado no e-mail.
        // Com a extensão de 180 min, o prazo efetivo passa pra 3h30 --
        // ainda dentro da janela de 24h (que é "faltam até 24h", não um
        // instante exato), mas fora da janela de 1h.
        $event = Event::factory()->create(['submission_deadline' => now()->addMinutes(30)]);
        Incident::factory()->for($event)->create(['deadline_extension_minutes' => 180]);
        $this->equipeSemSubmissao($event);

        $enviados = app(SendDeadlineReminders::class)->handle($event->fresh());

        $this->assertSame(1, $enviados['enviados_24h']);
        $this->assertSame(0, $enviados['enviados_1h']);
    }

    public function test_reminder_email_shows_the_extended_deadline_not_the_original(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addMinutes(30)]);
        Incident::factory()->for($event)->create(['deadline_extension_minutes' => 120]);
        $membro = $this->equipeSemSubmissao($event);

        app(SendDeadlineReminders::class)->handle($event->fresh());

        Notification::assertSentTo($membro->user, function (SubmissionDeadlineReminder $notification) use ($event) {
            $mail = $notification->toMail($notification);
            $prazoEfetivo = $event->fresh()->effectiveSubmissionDeadline()->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i');

            return str_contains(implode(' ', $mail->introLines), $prazoEfetivo);
        });
    }
}
