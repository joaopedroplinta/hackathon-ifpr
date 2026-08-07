<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\FindOrCreateGoogleUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(FindOrCreateGoogleUser $findOrCreate): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            // Sessão expirou entre o redirect e a volta, ou o state não bate.
            return to_route('login')->withErrors([
                'email' => 'A sessão expirou durante o login com Google. Tente novamente.',
            ]);
        } catch (\Throwable) {
            return to_route('login')->withErrors([
                'email' => 'Não foi possível entrar com o Google. Tente novamente ou use e-mail e senha.',
            ]);
        }

        try {
            $user = $findOrCreate->handle($googleUser);
        } catch (ValidationException $e) {
            return to_route('login')->withErrors($e->errors());
        }

        Auth::login($user, remember: true);

        request()->session()->regenerate();

        return to_route('dashboard');
    }
}
