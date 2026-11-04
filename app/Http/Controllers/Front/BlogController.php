<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentaireRequest;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $articles = Article::query()
            ->publies()
            ->with('auteur')
            ->withCount('commentairesApprouves')
            ->filtrer($request->only(['q', 'categorie']))
            ->latest('publie_le')
            ->paginate(6)
            ->withQueryString();

        $populaires = Article::publies()->orderByDesc('vues')->take(4)->get(['id', 'titre', 'slug', 'vues']);

        return view('modules.blog.front.index', compact('articles', 'populaires'));
    }

    public function show(Article $article): View
    {
        abort_unless(Article::publies()->whereKey($article->id)->exists(), 404);

        $article->increment('vues');
        $article->load(['auteur', 'commentairesApprouves.auteur']);

        $suggestions = Article::publies()
            ->where('categorie', $article->categorie)
            ->whereKeyNot($article->id)
            ->latest('publie_le')
            ->take(3)
            ->get();

        return view('modules.blog.front.show', compact('article', 'suggestions'));
    }

    public function commenter(CommentaireRequest $request, Article $article): RedirectResponse
    {
        abort_unless(Article::publies()->whereKey($article->id)->exists(), 404);

        $article->commentaires()->create([
            'user_id' => $request->user()->id,
            'contenu' => $request->validated('contenu'),
            'approuve' => true,
        ]);

        return redirect()
            ->to(route('front.blog.show', $article).'#commentaires')
            ->with('success', 'Merci pour votre commentaire !');
    }
}
