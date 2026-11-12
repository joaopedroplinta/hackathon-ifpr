<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Both check the current password (e-mail change, account delete).
    Route::patch('settings/profile', [ProfileController::class, 'update'])->middleware('throttle:6,1')->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->middleware('throttle:6,1')->name('profile.destroy');

    Route::post('settings/profile/foto', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('settings/profile/foto', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->middleware('throttle:6,1')->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');
});
