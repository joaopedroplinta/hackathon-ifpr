<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password!1',
            'password_confirmation' => 'Password!1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    /**
     * O texto exibido no formulário (PasswordRequirements) promete "letra
     * maiúscula, minúscula e um símbolo" -- sem isto o servidor podia
     * aceitar algo mais fraco do que o formulário anuncia.
     */
    public function test_password_without_the_required_complexity_is_rejected()
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('password')->assertRedirect('/register');
        $this->assertGuest();
    }
}
