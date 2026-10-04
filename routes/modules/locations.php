<?php

/*
|--------------------------------------------------------------------------
| Module 2 — Gestion des locations (Réservation + Paiement)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\PaiementController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Front\ReservationController as FrontReservationController;
use Illuminate\Support\Facades\Route;

// Back office : CRUD complet des deux entités
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('reservations', ReservationController::class);
    Route::resource('paiements', PaiementController::class);
});

// Front office : espace locataire
Route::middleware('auth')->name('front.reservations.')->group(function () {
    Route::get('/mes-reservations', [FrontReservationController::class, 'index'])->name('index');
    Route::get('/reserver', [FrontReservationController::class, 'create'])->name('create');
    Route::post('/reserver', [FrontReservationController::class, 'store'])->name('store');
    Route::get('/mes-reservations/{reservation}', [FrontReservationController::class, 'show'])->name('show');
    Route::patch('/mes-reservations/{reservation}/annuler', [FrontReservationController::class, 'annuler'])->name('annuler');
});
