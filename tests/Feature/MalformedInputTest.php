<?php

use App\Models\User;

it('rejects list-valued profile fields with 422 instead of 500', function (array $payload, string $field) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/settings/profile', $payload + ['name' => 'Fulano', 'email' => $user->email])
        ->assertStatus(422)
        ->assertJsonValidationErrors($field);
})->with([
    'email as list' => [['email' => ['invalid']], 'email'],
    'cpf as list' => [['cpf' => ['audit-invalid']], 'cpf'],
]);
