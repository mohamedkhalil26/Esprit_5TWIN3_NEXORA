<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Equipement;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    /**
     * Catalogue public : recherche, filtres et tri.
     */
    public function index(Request $request): View
    {
        $tri = $request->input('tri', 'recent');

        $equipements = Equipement::query()
            ->with(['categorie', 'proprietaire'])
            ->disponibles()
            ->filtrer($request->only(['q', 'categorie', 'ville', 'prix_max']))
            ->when($tri === 'prix_asc', fn ($q) => $q->orderBy('prix_jour'))
            ->when($tri === 'prix_desc', fn ($q) => $q->orderByDesc('prix_jour'))
            ->when($tri === 'recent', fn ($q) => $q->latest())
            ->paginate(9)
            ->withQueryString();

        $categories = Categorie::withCount(['equipements' => fn ($q) => $q->disponibles()])
            ->orderBy('nom')
            ->get();

        return view('modules.equipements.front.index', compact('equipements', 'categories', 'tri'));
    }

    public function show(Equipement $equipement): View
    {
        $equipement->load(['categorie', 'proprietaire']);

        $similaires = Equipement::with('categorie')
            ->disponibles()
            ->where('categorie_id', $equipement->categorie_id)
            ->whereKeyNot($equipement->id)
            ->inRandomOrder()
            ->take(3)
            ->get();

        return view('modules.equipements.front.show', compact('equipement', 'similaires'));
    }
}
