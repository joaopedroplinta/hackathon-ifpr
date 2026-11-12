<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;

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

it('pre-fills the reset-password page only from a string email', function (string $query, string $expected) {
    $this->get('/reset-password/some-token?'.$query)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/reset-password')->where('email', $expected));
})->with([
    'string' => ['email=fulano%40example.com', 'fulano@example.com'],
    'list' => ['email[x]=y', ''],
]);

it('treats a list-valued busca as no filter on staff searches', function (string $role, string $url, string $prop) {
    $this->seed(RoleSeeder::class);
    Event::factory()->create();
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    $this->actingAs($user)->get($url.'?busca=fulano')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where($prop, 'fulano'));

    $this->actingAs($user)->get($url.'?busca[x]=y')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where($prop, null));
})->with([
    'admin users' => [Role::Admin->value, '/admin/usuarios', 'filtros.busca'],
    'staff check-in' => [Role::Organizador->value, '/painel/checkin', 'busca'],
]);
