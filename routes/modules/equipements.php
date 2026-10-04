<?php

/*
|--------------------------------------------------------------------------
| Module 1 — Gestion des équipements (Catégorie + Équipement)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\CategorieController;
use App\Http\Controllers\Admin\EquipementController;
use App\Http\Controllers\Front\CatalogueController;
use Illuminate\Support\Facades\Route;

// Back office : CRUD complet des deux entités
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('categories', CategorieController::class)
        ->parameters(['categories' => 'categorie']);

    Route::resource('equipements', EquipementController::class);
});

// Front office : catalogue public
Route::get('/equipements', [CatalogueController::class, 'index'])->name('front.equipements.index');
Route::get('/equipements/{equipement}', [CatalogueController::class, 'show'])->name('front.equipements.show');
