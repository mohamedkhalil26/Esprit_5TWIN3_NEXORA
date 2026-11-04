<?php

/*
|--------------------------------------------------------------------------
| Module 5 — Blog & conseils énergie (Article + Commentaire)
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\CommentaireController;
use App\Http\Controllers\ArticleQuizController;
use App\Http\Controllers\Front\BlogController;
use Illuminate\Support\Facades\Route;

// Back office : CRUD complet des deux entités
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('articles', ArticleController::class);
    Route::resource('commentaires', CommentaireController::class);
    Route::patch('commentaires/{commentaire}/approbation', [CommentaireController::class, 'basculerApprobation'])
        ->name('commentaires.approbation');
});

// Front office : blog public (les articles sont identifiés par leur slug)
Route::get('/blog', [BlogController::class, 'index'])->name('front.blog.index');
Route::get('/blog/{article:slug}', [BlogController::class, 'show'])->name('front.blog.show');
Route::post('/blog/{article:slug}/commentaires', [BlogController::class, 'commenter'])
    ->middleware(['auth', 'verified'])
    ->name('front.blog.commenter');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/blog/{article:slug}/quiz', [ArticleQuizController::class, 'show'])->name('ai.quiz.show');
    Route::post('/blog/{article:slug}/quiz', [ArticleQuizController::class, 'generate'])->middleware('throttle:ai')->name('ai.quiz.generate');
    Route::post('/quiz/{quiz}/submit', [ArticleQuizController::class, 'submit'])->name('ai.quiz.submit');
});
