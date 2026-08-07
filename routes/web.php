<?php

use App\Http\Controllers\Participant\EventRegistrationController;
use App\Http\Controllers\Participant\TeamController;
use App\Http\Controllers\Participant\TeamMembershipController;
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

    Route::get('equipe/entrar', [TeamMembershipController::class, 'create'])->name('teams.join.create');
    Route::post('equipe/entrar', [TeamMembershipController::class, 'store'])->name('teams.join.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
