<?php

/*
|--------------------------------------------------------------------------
| Module 3 — Gestion des réclamations (Réclamation + Réponse)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\ReclamationController;
use App\Http\Controllers\Admin\ReponseReclamationController;
use App\Http\Controllers\Front\ReclamationController as FrontReclamationController;
use Illuminate\Support\Facades\Route;

// Back office : CRUD complet des deux entités
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('reclamations', ReclamationController::class);
    Route::resource('reponses', ReponseReclamationController::class)
        ->parameters(['reponses' => 'reponse']);
});

// Front office : espace membre
Route::middleware('auth')->prefix('mes-reclamations')->name('front.reclamations.')->group(function () {
    Route::get('/', [FrontReclamationController::class, 'index'])->name('index');
    Route::get('/nouvelle', [FrontReclamationController::class, 'create'])->name('create');
    Route::post('/', [FrontReclamationController::class, 'store'])->name('store');
    Route::get('/{reclamation}', [FrontReclamationController::class, 'show'])->name('show');
    Route::post('/{reclamation}/repondre', [FrontReclamationController::class, 'repondre'])->name('repondre');
});
