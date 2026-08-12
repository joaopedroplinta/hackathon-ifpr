<?php

namespace Tests\Feature\Console;

use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use App\Notifications\SubmissionDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendDeadlineRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_reminders_across_every_event_with_a_deadline(): void
    {
        Notification::fake();

        $event = Event::factory()->create(['submission_deadline' => now()->addHours(20)]);
        $team = Team::factory()->for($event)->create();
        TeamMember::factory()->for($event)->for($team)->lider()->create();

        Event::factory()->create(['submission_deadline' => null]);

        $this->artisan('hackathon:send-deadline-reminders')->assertExitCode(0);

        Notification::assertSentTimes(SubmissionDeadlineReminder::class, 1);
    }
}
