<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipementRequest;
use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EquipementController extends Controller
{
    /**
     * Liste des équipements avec leur catégorie et leur propriétaire (jointures).
     */
    public function index(Request $request): View
    {
        $equipements = Equipement::query()
            ->with(['categorie', 'proprietaire'])
            ->filtrer($request->only(['q', 'categorie', 'disponible']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.equipements.admin.equipements.index', [
            'equipements' => $equipements,
            'categories' => Categorie::orderBy('nom')->pluck('nom', 'id'),
        ]);
    }

    public function create(): View
    {
        return view('modules.equipements.admin.equipements.create', [
            'equipement' => new Equipement(['disponible' => true, 'etat' => 'bon']),
            ...$this->listesFormulaire(),
        ]);
    }

    public function store(EquipementRequest $request): RedirectResponse
    {
        $donnees = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $donnees['image'] = $this->enregistrerImage($request->file('image'));
        }

        $equipement = Equipement::create($donnees);

        return redirect()
            ->route('admin.equipements.show', $equipement)
            ->with('success', "L'équipement « {$equipement->nom} » a été ajouté.");
    }

    public function show(Equipement $equipement): View
    {
        $equipement->load(['categorie', 'proprietaire']);

        return view('modules.equipements.admin.equipements.show', compact('equipement'));
    }

    public function edit(Equipement $equipement): View
    {
        return view('modules.equipements.admin.equipements.edit', [
            'equipement' => $equipement,
            ...$this->listesFormulaire(),
        ]);
    }

    public function update(EquipementRequest $request, Equipement $equipement): RedirectResponse
    {
        $donnees = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->supprimerImage($equipement);
            $donnees['image'] = $this->enregistrerImage($request->file('image'));
        }

        $equipement->update($donnees);

        return redirect()
            ->route('admin.equipements.show', $equipement)
            ->with('success', "L'équipement « {$equipement->nom} » a été modifié.");
    }

    public function destroy(Equipement $equipement): RedirectResponse
    {
        $this->supprimerImage($equipement);
        $equipement->delete();

        return redirect()
            ->route('admin.equipements.index')
            ->with('success', "L'équipement « {$equipement->nom} » a été supprimé.");
    }

    /**
     * Données des listes déroulantes du formulaire (catégories et propriétaires).
     */
    private function listesFormulaire(): array
    {
        return [
            'categories' => Categorie::orderBy('nom')->pluck('nom', 'id'),
            'proprietaires' => User::orderBy('name')->get(['id', 'name', 'email']),
        ];
    }

    private function enregistrerImage(UploadedFile $fichier): string
    {
        $nom = Str::uuid().'.'.$fichier->getClientOriginalExtension();
        $fichier->move(public_path('uploads/equipements'), $nom);

        return 'uploads/equipements/'.$nom;
    }

    private function supprimerImage(Equipement $equipement): void
    {
        if ($equipement->image && File::exists(public_path($equipement->image))) {
            File::delete(public_path($equipement->image));
        }
    }
}
