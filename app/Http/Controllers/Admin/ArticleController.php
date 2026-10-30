<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /**
     * Liste des articles avec auteur et nombre de commentaires (jointures).
     */
    public function index(Request $request): View
    {
        $articles = Article::query()
            ->with('auteur')
            ->withCount('commentaires')
            ->filtrer($request->only(['q', 'categorie', 'statut']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.blog.admin.articles.index', compact('articles'));
    }

    public function create(Request $request): View
    {
        return view('modules.blog.admin.articles.create', [
            'article' => new Article(['statut' => 'brouillon', 'user_id' => $request->user()->id]),
            'auteurs' => User::orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function store(ArticleRequest $request): RedirectResponse
    {
        $donnees = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $donnees['image'] = $this->enregistrerImage($request->file('image'));
        }

        $article = Article::create($donnees);

        return redirect()
            ->route('admin.articles.show', $article)
            ->with('success', "L'article « {$article->titre} » a été enregistré.");
    }

    /**
     * Détail d'un article avec tous ses commentaires (jointure 1-N).
     */
    public function show(Article $article): View
    {
        $article->load(['auteur', 'commentaires.auteur']);

        return view('modules.blog.admin.articles.show', compact('article'));
    }

    public function edit(Article $article): View
    {
        return view('modules.blog.admin.articles.edit', [
            'article' => $article,
            'auteurs' => User::orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function update(ArticleRequest $request, Article $article): RedirectResponse
    {
        $donnees = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->supprimerImage($article);
            $donnees['image'] = $this->enregistrerImage($request->file('image'));
        }

        $article->update($donnees);

        return redirect()
            ->route('admin.articles.show', $article)
            ->with('success', "L'article « {$article->titre} » a été modifié.");
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->supprimerImage($article);
        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('success', "L'article « {$article->titre} » et ses commentaires ont été supprimés.");
    }

    private function enregistrerImage(UploadedFile $fichier): string
    {
        $nom = Str::uuid().'.'.$fichier->extension();
        $fichier->move(public_path('uploads/articles'), $nom);

        return 'uploads/articles/'.$nom;
    }

    private function supprimerImage(Article $article): void
    {
        if ($article->image && File::exists(public_path($article->image))) {
            File::delete(public_path($article->image));
        }
    }
}
