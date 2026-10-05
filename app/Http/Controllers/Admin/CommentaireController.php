<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentaireRequest;
use App\Models\Article;
use App\Models\Commentaire;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommentaireController extends Controller
{
    /**
     * Modération : tous les commentaires avec leur article et leur auteur (jointures).
     */
    public function index(Request $request): View
    {
        $commentaires = Commentaire::query()
            ->with(['article', 'auteur'])
            ->when($request->input('q'), fn ($q, $texte) => $q->where('contenu', 'like', "%{$texte}%"))
            ->when($request->input('article'), fn ($q, $id) => $q->where('article_id', $id))
            ->when($request->filled('approuve'), fn ($q) => $q->where('approuve', $request->boolean('approuve')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('modules.blog.admin.commentaires.index', [
            'commentaires' => $commentaires,
            'articles' => Article::orderBy('titre')->pluck('titre', 'id'),
            'enAttente' => Commentaire::where('approuve', false)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('modules.blog.admin.commentaires.create', [
            'commentaire' => new Commentaire([
                'article_id' => $request->integer('article') ?: null,
                'user_id' => $request->user()->id,
                'approuve' => true,
            ]),
            ...$this->listesFormulaire(),
        ]);
    }

    public function store(CommentaireRequest $request): RedirectResponse
    {
        $commentaire = Commentaire::create($request->validated());

        return redirect()
            ->route('admin.articles.show', $commentaire->article_id)
            ->with('success', 'Le commentaire a été ajouté.');
    }

    public function show(Commentaire $commentaire): View
    {
        $commentaire->load(['article', 'auteur']);

        return view('modules.blog.admin.commentaires.show', compact('commentaire'));
    }

    public function edit(Commentaire $commentaire): View
    {
        return view('modules.blog.admin.commentaires.edit', [
            'commentaire' => $commentaire,
            ...$this->listesFormulaire(),
        ]);
    }

    public function update(CommentaireRequest $request, Commentaire $commentaire): RedirectResponse
    {
        $commentaire->update($request->validated());

        return redirect()
            ->route('admin.commentaires.show', $commentaire)
            ->with('success', 'Le commentaire a été modifié.');
    }

    public function destroy(Commentaire $commentaire): RedirectResponse
    {
        $commentaire->delete();

        return back()->with('success', 'Le commentaire a été supprimé.');
    }

    /**
     * Approuver / masquer un commentaire en un clic.
     */
    public function basculerApprobation(Commentaire $commentaire): RedirectResponse
    {
        $commentaire->update(['approuve' => ! $commentaire->approuve]);

        return back()->with('success', $commentaire->approuve ? 'Commentaire approuvé.' : 'Commentaire masqué.');
    }

    private function listesFormulaire(): array
    {
        return [
            'articles' => Article::orderBy('titre')->pluck('titre', 'id'),
            'auteurs' => User::orderBy('name')->get(['id', 'name', 'email']),
        ];
    }
}
