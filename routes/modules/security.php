<?php

use App\Http\Controllers\Auth\SecurityController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/mot-de-passe-oublie', 'auth.security', ['mode' => 'forgot'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [SecurityController::class, 'forgot'])->middleware('throttle:5,1,password-email:')->name('password.email');
    Route::get('/reinitialiser/{token}', fn (string $token) => view('auth.security', ['mode' => 'reset', 'token' => $token]))->name('password.reset');
    Route::post('/reinitialiser', [SecurityController::class, 'reset'])->middleware('throttle:5,1,password-reset:')->name('password.update');
    Route::get('/double-authentification', [SecurityController::class, 'challenge'])->name('two-factor.challenge');
    Route::post('/double-authentification', [SecurityController::class, 'authenticate'])->middleware('throttle:5,1,totp-challenge:')->name('two-factor.authenticate');
});
Route::middleware('auth')->group(function () {
    Route::view('/verifier-email', 'auth.security', ['mode' => 'verify'])->name('verification.notice');
    Route::get('/verifier-email/{id}/{hash}', [SecurityController::class, 'verify'])->middleware(['signed', 'throttle:6,1,email-verify:'])->name('verification.verify');
    Route::post('/verifier-email', [SecurityController::class, 'resend'])->middleware('throttle:3,1,email-resend:')->name('verification.send');
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/mot-de-passe', [ProfileController::class, 'password'])->middleware('throttle:5,1,profile-password:')->name('profile.password');
    Route::delete('/profil', [ProfileController::class, 'destroy'])->middleware('throttle:5,1,profile-delete:')->name('profile.destroy');
    Route::get('/profil/2fa', [SecurityController::class, 'showSetup'])->name('two-factor.show');
    Route::get('/profil/2fa/confirmer', fn () => redirect()->route('two-factor.show'));
    Route::post('/profil/2fa', [SecurityController::class, 'setup'])->middleware('throttle:5,1,totp-setup:')->name('two-factor.setup');
    Route::post('/profil/2fa/confirmer', [SecurityController::class, 'confirm'])->middleware('throttle:5,1,totp-confirm:')->name('two-factor.confirm');
    Route::delete('/profil/2fa', [SecurityController::class, 'disable'])->middleware('throttle:5,1,totp-disable:')->name('two-factor.disable');
});
