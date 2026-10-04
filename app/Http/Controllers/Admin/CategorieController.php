<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategorieRequest;
use App\Models\Categorie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategorieController extends Controller
{
    /**
     * Liste des catégories avec le nombre d'équipements de chacune.
     */
    public function index(Request $request): View
    {
        $categories = Categorie::query()
            ->withCount('equipements')
            ->when($request->input('q'), fn ($query, $q) => $query->where('nom', 'like', "%{$q}%"))
            ->orderBy('nom')
            ->paginate(10)
            ->withQueryString();

        return view('modules.equipements.admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('modules.equipements.admin.categories.create', [
            'categorie' => new Categorie(['icone' => 'bi-sun']),
        ]);
    }

    public function store(CategorieRequest $request): RedirectResponse
    {
        $categorie = Categorie::create($request->validated());

        return redirect()
            ->route('admin.categories.show', $categorie)
            ->with('success', "La catégorie « {$categorie->nom} » a été créée.");
    }

    /**
     * Détail d'une catégorie avec ses équipements (jointure 1-N).
     */
    public function show(Categorie $categorie): View
    {
        $equipements = $categorie->equipements()
            ->with('proprietaire')
            ->latest()
            ->paginate(8);

        return view('modules.equipements.admin.categories.show', compact('categorie', 'equipements'));
    }

    public function edit(Categorie $categorie): View
    {
        return view('modules.equipements.admin.categories.edit', compact('categorie'));
    }

    public function update(CategorieRequest $request, Categorie $categorie): RedirectResponse
    {
        $categorie->update($request->validated());

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été modifiée.");
    }

    public function destroy(Categorie $categorie): RedirectResponse
    {
        if ($categorie->equipements()->exists()) {
            return back()->with('error', "Impossible de supprimer « {$categorie->nom} » : elle contient encore des équipements.");
        }

        $categorie->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "La catégorie « {$categorie->nom} » a été supprimée.");
    }
}
