<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\AnonymizeUser;
use App\Enums\TipoVinculo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Http\Requests\Settings\UpdateAvatarRequest;
use App\Support\AvatarStorage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     *
     * cpf fica fora do $hidden só aqui, explícito -- o mesmo motivo do
     * qr_token em CredentialController: quem pede é sempre o dono da
     * própria conta, nunca serialização genérica do model.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'identidade' => [
                'cpf' => $user->cpf,
                'tipo_vinculo' => $user->tipo_vinculo?->value,
                'matricula_suap' => $user->matricula_suap,
                'matricula_siape' => $user->matricula_siape,
            ],
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Matrícula do vínculo que a pessoa não tem mais não fica esquecida
        // no banco -- quem era aluno e virou "externo" não continua com um
        // SUAP desatualizado exibido em lugar nenhum, mas guardado.
        if (($data['tipo_vinculo'] ?? null) !== TipoVinculo::AlunoIfpr->value) {
            $data['matricula_suap'] = null;
        }

        if (($data['tipo_vinculo'] ?? null) !== TipoVinculo::ProfessorIfpr->value) {
            $data['matricula_siape'] = null;
        }

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Troca a foto de perfil. Disco `public` de propósito -- diferente de
     * arquivo de submissão, avatar não é conteúdo controlado: aparece pra
     * qualquer colega de equipe, jurado e organizador, então não faz
     * sentido gatear atrás de rota autorizada (.claude/rules/security.md
     * fala de upload de submissão/edital, não disto).
     */
    public function updateAvatar(UpdateAvatarRequest $request): RedirectResponse
    {
        $user = $request->user();
        $antigoAvatar = $user->avatar_url;

        // Nome gerado pelo sistema, nunca o original -- mesma regra do
        // upload de submissão, mesmo não sendo conteúdo sigiloso aqui.
        $caminho = $request->file('foto')->storeAs(
            'avatars',
            Str::uuid().'.'.$request->file('foto')->extension(),
            'public',
        );

        $user->forceFill(['avatar_url' => Storage::disk('public')->url($caminho)])->save();

        AvatarStorage::apagarSeLocal($antigoAvatar);

        return to_route('profile.edit')->with('sucesso', 'Foto de perfil atualizada.');
    }

    /**
     * Volta pro círculo com as iniciais. Só apaga o arquivo do disco se ele
     * for nosso -- uma foto vinda do Google é uma URL externa, não um
     * caminho em storage/app/public.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        AvatarStorage::apagarSeLocal($user->avatar_url);
        $user->forceFill(['avatar_url' => null])->save();

        return to_route('profile.edit')->with('sucesso', 'Foto de perfil removida.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        app(AnonymizeUser::class)->handle($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
