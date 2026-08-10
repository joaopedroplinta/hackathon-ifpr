<?php

use App\Http\Controllers\Participant\EventRegistrationController;
use App\Http\Controllers\Participant\SubmissionController;
use App\Http\Controllers\Participant\TeamController;
use App\Http\Controllers\Participant\TeamInviteController;
use App\Http\Controllers\Participant\TeamLeadershipController;
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

    Route::delete('equipe/membros/{membership}', [TeamMembershipController::class, 'destroy'])
        ->name('teams.leave');
    Route::delete('equipe/membros/{membership}/remover', [TeamMembershipController::class, 'remove'])
        ->name('teams.members.remove');
    Route::patch('equipe/{team}/lideranca', [TeamLeadershipController::class, 'update'])
        ->name('teams.leadership.update');

    // Convite por e-mail. 'aceitar' fica fora de /equipe porque o link vem
    // de fora (e-mail) e o token já identifica o convite -- ver
    // TeamInviteController.
    Route::post('equipe/convites', [TeamInviteController::class, 'store'])->name('team-invites.store');
    Route::get('convites/{invite:token}/aceitar', [TeamInviteController::class, 'accept'])->name('team-invites.accept');

    // Submissao do projeto. Salvar rascunho e enviar sao rotas separadas
    // porque as exigencias sao outras: rascunho aceita campo em branco, o
    // envio nao -- ver SaveSubmissionRequest e SubmitSubmissionRequest.
    Route::get('submissao', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::post('submissao', [SubmissionController::class, 'save'])->name('submissions.save');
    Route::post('submissao/enviar', [SubmissionController::class, 'submit'])->name('submissions.submit');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
