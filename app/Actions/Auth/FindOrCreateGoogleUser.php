<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class FindOrCreateGoogleUser
{
    /**
     * Resolve a conta local a partir do perfil devolvido pelo Google.
     *
     * @throws ValidationException quando o e-mail não é confiável ou está
     *                             fora do domínio permitido.
     */
    public function handle(SocialiteUser $googleUser): User
    {
        $email = $googleUser->getEmail();

        if (! $email) {
            throw ValidationException::withMessages([
                'email' => 'Sua conta Google não expôs um e-mail. Use o cadastro com e-mail e senha.',
            ]);
        }

        $this->assertEmailIsVerifiedByGoogle($googleUser);
        $this->assertDomainIsAllowed($email);

        // Já vinculada: só atualiza o que pode ter mudado no Google.
        $byGoogleId = User::where('google_id', $googleUser->getId())->first();

        if ($byGoogleId) {
            $byGoogleId->forceFill(['avatar_url' => $googleUser->getAvatar()])->save();

            return $byGoogleId;
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            return $this->linkToExistingAccount($existing, $googleUser);
        }

        return $this->createAccount($googleUser, $email);
    }

    /**
     * O Google diz se ele mesmo verificou o endereço. Sem essa confirmação
     * o e-mail não vale como identidade — seria possível reivindicar a conta
     * de outra pessoa com um endereço não confirmado.
     */
    private function assertEmailIsVerifiedByGoogle(SocialiteUser $googleUser): void
    {
        $verified = data_get($googleUser->getRaw(), 'email_verified');

        if ($verified !== true && $verified !== 'true') {
            throw ValidationException::withMessages([
                'email' => 'O Google não confirmou este e-mail. Verifique a conta no Google e tente de novo.',
            ]);
        }
    }

    private function assertDomainIsAllowed(string $email): void
    {
        $allowed = config('services.google.allowed_domain');

        if (blank($allowed)) {
            return;
        }

        $domain = str($email)->afterLast('@')->lower()->value();

        if ($domain !== str($allowed)->lower()->value()) {
            throw ValidationException::withMessages([
                'email' => "O login com Google está restrito a contas @{$allowed}. Use o cadastro com e-mail e senha.",
            ]);
        }
    }

    private function linkToExistingAccount(User $user, SocialiteUser $googleUser): User
    {
        $user->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(),
            // O Google já confirmou o endereço; não faz sentido pedir de novo.
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    private function createAccount(SocialiteUser $googleUser, string $email): User
    {
        $user = new User;

        $user->forceFill([
            'name' => $googleUser->getName() ?: str($email)->before('@')->value(),
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(),
            'email_verified_at' => now(),
            'password' => null,
        ])->save();

        $user->assignRole(Role::Participante->value);

        return $user;
    }
}
