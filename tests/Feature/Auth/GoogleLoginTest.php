<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function fakeGoogleUser(array $overrides = []): SocialiteUser
    {
        $attributes = array_merge([
            'id' => '1234567890',
            'name' => 'Maria Silva',
            'email' => 'maria@ifpr.edu.br',
            'avatar' => 'https://lh3.googleusercontent.com/avatar.jpg',
            'email_verified' => true,
        ], $overrides);

        $user = new SocialiteUser;
        $user->map([
            'id' => $attributes['id'],
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'avatar' => $attributes['avatar'],
        ]);
        $user->setRaw(['email_verified' => $attributes['email_verified']]);

        return $user;
    }

    private function mockGoogleReturning(SocialiteUser $user): void
    {
        $provider = \Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_first_google_login_creates_a_verified_participant(): void
    {
        $this->mockGoogleReturning($this->fakeGoogleUser());

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'maria@ifpr.edu.br')->firstOrFail();

        $this->assertSame('1234567890', $user->google_id);
        $this->assertNull($user->password, 'Conta só-Google não deve ter senha.');
        $this->assertNotNull($user->email_verified_at, 'O Google já confirmou o endereço.');
        $this->assertTrue($user->hasRole(Role::Participante->value));
        $this->assertAuthenticatedAs($user);
    }

    public function test_second_login_reuses_the_same_account(): void
    {
        $this->mockGoogleReturning($this->fakeGoogleUser());
        $this->get(route('google.callback'));

        $this->post(route('logout'));

        $this->mockGoogleReturning($this->fakeGoogleUser());
        $this->get(route('google.callback'));

        $this->assertSame(1, User::where('email', 'maria@ifpr.edu.br')->count());
    }

    public function test_google_links_to_an_existing_account_with_the_same_email(): void
    {
        $existing = User::factory()->create([
            'email' => 'maria@ifpr.edu.br',
            'email_verified_at' => null,
        ]);

        $this->mockGoogleReturning($this->fakeGoogleUser());

        $this->get(route('google.callback'));

        $existing->refresh();

        $this->assertSame('1234567890', $existing->google_id, 'Deve vincular, não criar conta duplicada.');
        $this->assertNotNull($existing->email_verified_at);
        $this->assertSame(1, User::count());
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->mockGoogleReturning($this->fakeGoogleUser(['email_verified' => false]));

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $this->assertSame(0, User::count(), 'Nenhuma conta pode ser criada a partir de e-mail não confirmado.');
        $this->assertGuest();
    }

    public function test_domain_restriction_blocks_outside_accounts(): void
    {
        config(['services.google.allowed_domain' => 'ifpr.edu.br']);

        $this->mockGoogleReturning($this->fakeGoogleUser(['email' => 'alguem@gmail.com']));

        $response = $this->get(route('google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function test_domain_restriction_allows_the_configured_domain(): void
    {
        config(['services.google.allowed_domain' => 'ifpr.edu.br']);

        $this->mockGoogleReturning($this->fakeGoogleUser());

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::count());
    }

    public function test_empty_domain_config_allows_any_google_account(): void
    {
        config(['services.google.allowed_domain' => null]);

        $this->mockGoogleReturning($this->fakeGoogleUser(['email' => 'alguem@gmail.com']));

        $this->get(route('google.callback'))->assertRedirect(route('dashboard'));

        $this->assertSame(1, User::count());
    }

    public function test_login_page_offers_the_google_button(): void
    {
        $this->get(route('login'))->assertOk();

        $this->assertSame(
            url('/auth/google/redirect'),
            route('google.redirect'),
            'A rota do botão precisa existir com este caminho.'
        );
    }
}
