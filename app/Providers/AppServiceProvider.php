<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Regra única pros três lugares que criam senha (cadastro, troca em
        // Configurações, redefinição por e-mail) -- todos já chamam
        // Password::defaults(), mas sem isto o padrão do Laravel é só
        // "8 caracteres", sem maiúscula/minúscula/símbolo. O texto exibido
        // no formulário (auth/register.tsx, settings/password.tsx,
        // auth/reset-password.tsx) precisa continuar batendo com isto se a
        // regra mudar.
        Password::defaults(fn () => Password::min(8)->mixedCase()->symbols());
    }
}
