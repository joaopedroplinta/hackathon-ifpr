<?php

namespace Tests\Feature\Settings;

use App\Actions\Users\MarkTourSeen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourSeenTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_mark_a_tour(): void
    {
        $this->post('/tours/dashboard')->assertRedirect('/login');
    }

    public function test_marking_adds_the_tour_to_the_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/dashboard')->post('/tours/dashboard')->assertRedirect('/dashboard');

        $this->assertSame(['dashboard'], $user->refresh()->tours_seen);
    }

    public function test_marking_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tours/dashboard');
        $this->actingAs($user)->post('/tours/dashboard');

        $this->assertSame(['dashboard'], $user->refresh()->tours_seen);
    }

    public function test_invalid_format_is_rejected(): void
    {
        $user = User::factory()->create();

        foreach (['Dashboard', 'a b', str_repeat('a', 65), 'x_y', "dashboard\n"] as $tour) {
            $this->actingAs($user)->from('/dashboard')->post('/tours/'.rawurlencode($tour))->assertSessionHasErrors('tour');
        }

        $this->assertSame([], $user->refresh()->tours_seen);
    }

    public function test_list_is_capped_dropping_the_oldest(): void
    {
        $user = User::factory()->create();

        foreach (range(1, MarkTourSeen::LIMIT + 1) as $i) {
            (new MarkTourSeen)->handle($user, "tour.{$i}");
        }

        $seen = $user->refresh()->tours_seen;
        $this->assertCount(MarkTourSeen::LIMIT, $seen);
        $this->assertSame('tour.2', $seen[0]);
        $this->assertSame('tour.'.(MarkTourSeen::LIMIT + 1), end($seen));
    }

    public function test_shared_auth_user_exposes_tours_seen(): void
    {
        $user = User::factory()->create();
        (new MarkTourSeen)->handle($user, 'dashboard');

        $this->actingAs($user)->get('/dashboard')->assertInertia(
            fn ($page) => $page->where('auth.user.tours_seen', ['dashboard'])
        );
    }
}
