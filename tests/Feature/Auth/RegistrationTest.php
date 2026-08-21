<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmailQueued;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
     * Regressão: a notificação padrão de verificação de e-mail não é
     * ShouldQueue -- ela era enviada dentro do próprio request de cadastro,
     * então uma falha do provedor de e-mail (Resend fora do ar, chave
     * errada, domínio de teste rejeitado) derrubava o cadastro inteiro com
     * 500, mesmo com o usuário já criado no banco. Ver VerifyEmailQueued.
     */
    public function test_registration_sends_a_queued_verification_email(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password!1',
            'password_confirmation' => 'Password!1',
        ]);

        Notification::assertSentTo(User::firstWhere('email', 'test@example.com'), VerifyEmailQueued::class);
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
