<?php

use App\Models\User;
use Illuminate\Support\Str;

/*
 * Every endpoint that checks a password or a secret code is rate-limited
 * (charter XI). The first request goes through; the one past the limit
 * gets 429 before the controller runs.
 */
it('rate-limits password and secret-code endpoints', function (string $method, string $url, int $limit, bool $auth) {
    $payload = ['password' => 'wrong', 'current_password' => 'wrong'];

    if ($auth) {
        $this->actingAs(User::factory()->create());
    }

    $send = fn () => $this->json($method, $url, $payload);

    expect($send()->status())->not->toBe(429);

    for ($i = 1; $i < $limit; $i++) {
        $send();
    }

    $send()->assertStatus(429);
})->with([
    'confirm password' => ['POST', '/confirm-password', 6, true],
    'password update' => ['PUT', '/settings/password', 6, true],
    'profile update' => ['PATCH', '/settings/profile', 6, true],
    'account delete' => ['DELETE', '/settings/profile', 6, true],
    'certificate validation' => ['GET', '/validar/'.Str::uuid(), 30, false],
    'invite accept' => ['GET', '/convites/'.Str::random(40).'/aceitar', 10, true],
]);
