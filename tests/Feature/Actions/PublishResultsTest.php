<?php

namespace Tests\Feature\Actions;

use App\Actions\Results\PublishResults;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\ResultsPublished;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

        app(PublishResults::class)->handle($event);

        Notification::assertSentTo($inscrito, ResultsPublished::class);
        $this->assertNotNull($event->fresh()->results_published_at);
    }

    public function test_does_not_notify_again_when_republishing(): void
    {
        Notification::fake();

        $event = Event::factory()->create();
        $inscrito = User::factory()->create();
        EventRegistration::factory()->for($event)->for($inscrito)->create();

        app(PublishResults::class)->handle($event);
        app(PublishResults::class)->handle($event->fresh());

        Notification::assertSentTimes(ResultsPublished::class, 1);
    }
}
