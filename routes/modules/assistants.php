<?php

use App\Http\Controllers\Admin\EventBlueprintController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AiConversationController;
use App\Http\Controllers\AiFeedbackController;
use App\Http\Controllers\AiInsightsController;
use App\Http\Controllers\Front\AiRecommendationController;
use Illuminate\Support\Facades\Route;

Route::get('/recommandations/energie', [AiRecommendationController::class, 'energy'])->name('ai.recommendations.energy');
Route::get('/recommandations/evenements', [AiRecommendationController::class, 'events'])->name('ai.recommendations.events');
Route::get('/assistants', [AiController::class, 'index'])->name('ai.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/recommandations/reservation', [AiRecommendationController::class, 'booking'])->name('ai.recommendations.booking');
    Route::post('/assistants/feedback', [AiFeedbackController::class, 'store'])->middleware('throttle:10,1,ai-feedback')->name('ai.feedback.store');
    Route::delete('/assistants/feedback/{feedback}', [AiFeedbackController::class, 'destroy'])->name('ai.feedback.destroy');
    Route::get('/assistants/{module}', [AiController::class, 'show'])->name('ai.show');
    Route::post('/assistants/{module}', [AiController::class, 'generate'])->middleware('throttle:ai')->name('ai.generate');
    Route::post('/assistants/{module}/{id}/valider', [AiController::class, 'apply'])->middleware(['admin', 'throttle:10,1,ai-apply:'])->whereNumber('id')->name('ai.apply');

    Route::get('/assistants/front/{module}', [AiConversationController::class, 'showFront'])->name('ai.front.show');
    Route::post('/assistants/front/{module}/messages', [AiConversationController::class, 'sendFront'])->middleware('throttle:ai')->name('ai.front.send');
    Route::delete('/assistants/front/{module}/conversations/{conversation}', [AiConversationController::class, 'destroyFront'])->whereNumber('conversation')->name('ai.front.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin/assistants')->name('ai.admin.')->group(function () {
    Route::get('/blueprints', [EventBlueprintController::class, 'index'])->name('blueprints.index');
    Route::get('/blueprints/create', [EventBlueprintController::class, 'create'])->name('blueprints.create');
    Route::post('/blueprints/generate', [EventBlueprintController::class, 'generate'])->middleware('throttle:ai')->name('blueprints.generate');
    Route::post('/blueprints', [EventBlueprintController::class, 'store'])->name('blueprints.store');
    Route::get('/blueprints/{blueprint}/edit', [EventBlueprintController::class, 'edit'])->name('blueprints.edit');
    Route::put('/blueprints/{blueprint}', [EventBlueprintController::class, 'update'])->name('blueprints.update');
    Route::delete('/blueprints/{blueprint}', [EventBlueprintController::class, 'destroy'])->name('blueprints.destroy');
    Route::get('/blueprints/{blueprint}/prefill', [EventBlueprintController::class, 'prefill'])->name('blueprints.prefill');
    Route::get('/insights', [AiInsightsController::class, 'index'])->name('insights.index');
    Route::get('/feedback', [AiFeedbackController::class, 'index'])->name('feedback.index');
    Route::get('/{module}', [AiConversationController::class, 'showAdmin'])->name('show');
    Route::post('/{module}/messages', [AiConversationController::class, 'sendAdmin'])->middleware('throttle:ai')->name('send');
    Route::delete('/{module}/conversations/{conversation}', [AiConversationController::class, 'destroyAdmin'])->whereNumber('conversation')->name('destroy');
});
