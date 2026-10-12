<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('rejects a list as e-mail on register with 422 instead of 500', function () {
    $this->postJson('/register', [
        'name' => 'X', 'email' => ['invalid'],
        'password' => 'Abcdef1!', 'password_confirmation' => 'Abcdef1!',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    expect(User::count())->toBe(0);
});

it('rejects a password with a null byte on register', function () {
    $this->postJson('/register', [
        'name' => 'X', 'email' => 'nulo@example.com',
        'password' => "Abcdef1!\0", 'password_confirmation' => "Abcdef1!\0",
    ])->assertStatus(422)->assertJsonValidationErrors('password');

    expect(User::count())->toBe(0);
});

it('rejects a list as reset token even when a real token exists', function () {
    $user = User::factory()->create();
    Password::broker()->createToken($user);

    $this->postJson('/reset-password', [
        'token' => ['x'], 'email' => $user->email,
        'password' => 'Abcdef1!', 'password_confirmation' => 'Abcdef1!',
    ])->assertStatus(422)->assertJsonValidationErrors('token');
});

it('answers the same for unknown e-mail and invalid token on reset', function () {
    $user = User::factory()->create();
    $body = ['token' => 'errado', 'password' => 'Abcdef1!', 'password_confirmation' => 'Abcdef1!'];

    $known = $this->postJson('/reset-password', $body + ['email' => $user->email])->json('errors.email');
    $unknown = $this->postJson('/reset-password', $body + ['email' => 'ninguem@example.com'])->json('errors.email');

    expect($unknown)->toBe($known);
});

it('throttles repeated forgot-password requests per origin', function () {
    Notification::fake();

    foreach (range(1, 10) as $i) {
        $this->postJson('/forgot-password', ['email' => "a{$i}@example.com"])->assertRedirect();
    }

    $this->postJson('/forgot-password', ['email' => 'a11@example.com'])->assertStatus(429);
});
