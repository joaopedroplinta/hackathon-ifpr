<?php

namespace Tests\Feature\Actions;

use App\Actions\Results\PublishResults;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\ResultsPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class PublishResultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_every_registered_user_on_first_publish(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $inscrito = User::factory()->create();
        EventRegistration::factory()->for($event)->for($inscrito)->create();
        $organizador = User::factory()->create();

        app(PublishResults::class)->handle($event, $organizador);

        Notification::assertSentTo($inscrito, ResultsPublished::class);
        $this->assertNotNull($event->fresh()->results_published_at);
    }

    public function test_does_not_notify_again_when_republishing(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $inscrito = User::factory()->create();
        EventRegistration::factory()->for($event)->for($inscrito)->create();
        $organizador = User::factory()->create();

        app(PublishResults::class)->handle($event, $organizador);
        app(PublishResults::class)->handle($event->fresh(), $organizador);

        Notification::assertSentTimes(ResultsPublished::class, 1);
    }

    /** .claude/rules/security.md, "Auditoria" -- publicação de resultado precisa de autor, horário e motivo. */
    public function test_logs_the_author_and_whether_pendencies_were_overridden(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $organizador = User::factory()->create();

        app(PublishResults::class)->handle($event, $organizador, comPendencia: true);

        $registro = Activity::latest()->first();

        $this->assertNotNull($registro);
        $this->assertSame('Resultado publicado', $registro->description);
        $this->assertSame($event->id, $registro->subject_id);
        $this->assertSame($organizador->id, $registro->causer_id);
        $this->assertTrue($registro->properties->get('com_pendencia'));
    }
}
