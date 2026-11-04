<?php

use App\Http\Controllers\AiGlobalSearchController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SmartNotificationController;
use App\Http\Controllers\ToolsController;
use Illuminate\Support\Facades\Route;

Route::get('/comparateur', [ToolsController::class, 'compare'])->name('tools.compare');
Route::get('/recherche-intelligente', [AiGlobalSearchController::class, 'index'])->name('ai.search');
Route::post('/langue', [LocaleController::class, 'update'])->name('locale.update');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', [SmartNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/lue', [SmartNotificationController::class, 'read'])->name('notifications.read');
});
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pieces-jointes/{reclamation}', [ToolsController::class, 'attachment'])->name('reclamations.attachment');
    Route::get('/disponibilites', [ToolsController::class, 'availability'])->name('tools.availability');
    Route::get('/mes-favoris', [ToolsController::class, 'favorites'])->name('favorites.index');
    Route::post('/favoris/{article}', [ToolsController::class, 'favorite'])->name('favorites.store');
    Route::delete('/favoris/{article}', [ToolsController::class, 'unfavorite'])->name('favorites.destroy');
});
