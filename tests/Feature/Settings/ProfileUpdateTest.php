<?php

namespace Tests\Feature\Settings;

use App\Models\Certificate;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/settings/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();

        // fresh() ignora o soft delete por padrão -- find() é quem reflete
        // o que toda consulta normal (login, listagem) de fato enxerga.
        $this->assertNull(User::find($user->id));
        $this->assertTrue(User::withTrashed()->find($user->id)->trashed());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/settings/profile')
            ->delete('/settings/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/profile');

        $this->assertNotNull($user->fresh());
    }

    /**
     * certificates.user_id é restrictOnDelete() de propósito (registro
     * histórico) -- sem soft delete + anonimização, isso quebrava com
     * violação de foreign key pra qualquer pessoa com certificado emitido.
     */
    public function test_deleting_the_account_does_not_break_for_a_user_with_a_certificate(): void
    {
        $user = User::factory()->create();
        $registration = EventRegistration::factory()->for($user)->create([
            'phone' => '(41) 90000-0000',
            'course' => 'Análise e Desenvolvimento de Sistemas',
        ]);
        $certificate = Certificate::factory()->for($user)->create();

        $response = $this
            ->actingAs($user)
            ->delete('/settings/profile', ['password' => 'password']);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));

        $user = User::withTrashed()->find($user->id);
        $this->assertSame('Usuário removido', $user->name);
        $this->assertSame("removido-{$user->id}@removido.local", $user->email);
        $this->assertTrue($user->trashed());

        $registration->refresh();
        $this->assertNull($registration->phone);
        $this->assertNull($registration->course);

        // O registro histórico continua existindo, só a pessoa some.
        $this->assertNotNull($certificate->fresh());
    }
}
