<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordQueued;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sessão roubada não pode sobreviver à troca nem ao reset de senha. Usa o
 * driver `database` de verdade: com `array` não existe "outro navegador".
 */
class SessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = 'OldPassw0rd!x';

    private const NEW = 'NewPassw0rd!yy';

    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'database']);
        $this->seed(RoleSeeder::class);
    }

    private function newBrowser(): void
    {
        $this->app['session.store']->flush();
        $this->app['auth']->forgetGuards();
    }

    private function loginAndGetSessionId(User $user, string $password): string
    {
        $this->newBrowser();
        $before = DB::table('sessions')->pluck('id')->all();

        $this->post('/login', ['email' => $user->email, 'password' => $password])->assertRedirect('/dashboard');

        return DB::table('sessions')->where('user_id', $user->id)->whereNotIn('id', $before)->firstOrFail()->id;
    }

    private function dashboardStatusWith(string $sessionId): int
    {
        $this->newBrowser();

        return $this->withCookie(config('session.cookie'), $sessionId)->get('/dashboard')->status();
    }

    public function test_changing_the_password_logs_out_the_other_sessions_but_keeps_the_current_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::OLD)]);
        $current = $this->loginAndGetSessionId($user, self::OLD);
        $stolen = $this->loginAndGetSessionId($user, self::OLD);

        $this->assertSame(200, $this->dashboardStatusWith($stolen));

        $this->newBrowser();
        $this->withCookie(config('session.cookie'), $current)->put('/settings/password', [
            'current_password' => self::OLD,
            'password' => self::NEW,
            'password_confirmation' => self::NEW,
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check(self::NEW, $user->fresh()->password));
        $this->assertSame(302, $this->dashboardStatusWith($stolen), 'sessão antiga ainda autentica');
        $this->assertSame(200, $this->dashboardStatusWith($current), 'a sessão de quem trocou a senha caiu');
    }

    public function test_resetting_the_password_logs_out_every_existing_session(): void
    {
        Notification::fake();

        $user = User::factory()->create(['password' => Hash::make(self::OLD)]);
        $stolen = $this->loginAndGetSessionId($user, self::OLD);

        $this->newBrowser();
        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPasswordQueued::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->newBrowser();
        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => self::NEW,
            'password_confirmation' => self::NEW,
        ])->assertRedirect('/login');

        $this->assertSame(302, $this->dashboardStatusWith($stolen), 'sessão antiga sobreviveu ao reset');
    }
}
