<?php

namespace Tests\Feature\Participant;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_qr_token_is_generated_when_the_user_is_created(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->qr_token);
        $this->assertTrue(Str::isUuid($user->qr_token));
    }

    public function test_the_qr_token_is_never_sequential_and_always_unique(): void
    {
        $primeiro = User::factory()->create();
        $segundo = User::factory()->create();

        $this->assertNotSame($primeiro->qr_token, $segundo->qr_token);
        // uuid v4 -- não sequencial nem derivado do id, .claude/rules/security.md.
        $this->assertTrue(Str::isUuid($primeiro->qr_token));
        $this->assertTrue(Str::isUuid($segundo->qr_token));
    }

    public function test_the_user_sees_their_own_credential(): void
    {
        $user = User::factory()->create(['name' => 'Ana Torres', 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('credencial.show'))
            ->assertOk()
            ->assertInertia(
                fn (AssertableInertia $page) => $page
                    ->component('credencial/mostrar')
                    ->where('nome', 'Ana Torres')
                    ->where('token', $user->qr_token)
                    ->has('qr_svg')
            );
    }

    /** A rota nunca recebe id: o token na resposta é sempre o de quem está logado. */
    public function test_a_user_never_sees_another_user_token(): void
    {
        $alvo = User::factory()->create(['email_verified_at' => now()]);
        $intruso = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($intruso)
            ->get(route('credencial.show'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('token', $intruso->qr_token)->whereNot('token', $alvo->qr_token));
    }

    public function test_the_qr_token_never_leaks_through_default_serialization(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('qr_token', $user->toArray());
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('credencial.show'))->assertRedirect(route('login'));
    }
}
