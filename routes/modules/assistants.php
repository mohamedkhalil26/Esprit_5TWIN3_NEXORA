<?php

use App\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/assistants/{module}', [AiController::class, 'show'])->name('ai.show');
    Route::post('/assistants/{module}', [AiController::class, 'generate'])->middleware('throttle:ai')->name('ai.generate');
    Route::post('/assistants/{module}/{id}/valider', [AiController::class, 'apply'])->middleware(['admin', 'throttle:10,1,ai-apply:'])->whereNumber('id')->name('ai.apply');
});
