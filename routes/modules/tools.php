<?php

use App\Http\Controllers\ToolsController;
use Illuminate\Support\Facades\Route;

Route::get('/comparateur', [ToolsController::class, 'compare'])->name('tools.compare');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pieces-jointes/{reclamation}', [ToolsController::class, 'attachment'])->name('reclamations.attachment');
    Route::get('/disponibilites', [ToolsController::class, 'availability'])->name('tools.availability');
    Route::get('/mes-favoris', [ToolsController::class, 'favorites'])->name('favorites.index');
    Route::post('/favoris/{article}', [ToolsController::class, 'favorite'])->name('favorites.store');
    Route::delete('/favoris/{article}', [ToolsController::class, 'unfavorite'])->name('favorites.destroy');
});
