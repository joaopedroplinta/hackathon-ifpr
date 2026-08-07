<?php

use App\Http\Controllers\Participant\EventRegistrationController;
use App\Http\Controllers\Participant\TeamController;
use App\Http\Controllers\Participant\TeamInviteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Inscricao no evento. 'verified' porque e-mail nao confirmado nao pode
// virar membro de equipe -- ver EventPolicy::register.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('inscricao', [EventRegistrationController::class, 'create'])
        ->name('registration.create');

    Route::post('inscricao', [EventRegistrationController::class, 'store'])
        ->name('registration.store');

    Route::get('equipe', [TeamController::class, 'show'])->name('teams.show');
    Route::get('equipe/criar', [TeamController::class, 'create'])->name('teams.create');
    Route::post('equipe', [TeamController::class, 'store'])->name('teams.store');

    // Convite por e-mail. 'aceitar' fica fora de /equipe porque o link vem
    // de fora (e-mail) e o token já identifica o convite -- ver
    // TeamInviteController.
    Route::post('equipe/convites', [TeamInviteController::class, 'store'])->name('team-invites.store');
    Route::get('convites/{invite:token}/aceitar', [TeamInviteController::class, 'accept'])->name('team-invites.accept');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
