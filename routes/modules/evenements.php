<?php

/*
|--------------------------------------------------------------------------
| Module 4 — Gestion des événements (Événement + Participation)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\EvenementController;
use App\Http\Controllers\Admin\ParticipationController;
use App\Http\Controllers\Front\EvenementController as FrontEvenementController;
use Illuminate\Support\Facades\Route;

// Back office : CRUD complet des deux entités
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('evenements', EvenementController::class);
    Route::resource('participations', ParticipationController::class);
});

// Front office
Route::get('/evenements', [FrontEvenementController::class, 'index'])->name('front.evenements.index');
Route::get('/evenements/{evenement}', [FrontEvenementController::class, 'show'])->name('front.evenements.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/evenements/{evenement}/inscription', [FrontEvenementController::class, 'inscrire'])->name('front.evenements.inscrire');
    Route::delete('/evenements/{evenement}/inscription', [FrontEvenementController::class, 'desinscrire'])->name('front.evenements.desinscrire');
    Route::get('/mes-evenements', [FrontEvenementController::class, 'mesEvenements'])->name('front.evenements.mes');
});
