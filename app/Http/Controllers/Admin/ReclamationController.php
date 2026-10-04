<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReclamationRequest;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    /**
     * Liste des réclamations avec leur auteur et le nombre de réponses (jointures).
     */
    public function index(Request $request): View
    {
        $reclamations = Reclamation::query()
            ->with('auteur')
            ->withCount('reponses')
            ->filtrer($request->only(['q', 'statut', 'priorite', 'categorie']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $compteurs = Reclamation::selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return view('modules.reclamations.admin.reclamations.index', compact('reclamations', 'compteurs'));
    }

    public function create(): View
    {
        return view('modules.reclamations.admin.reclamations.create', [
            'reclamation' => new Reclamation(['priorite' => 'moyenne', 'statut' => 'ouverte']),
            'membres' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(ReclamationRequest $request): RedirectResponse
    {
        $donnees = $request->safe()->except('piece_jointe');

        if ($request->hasFile('piece_jointe')) {
            $donnees['piece_jointe'] = $this->enregistrerFichier($request->file('piece_jointe'));
        }

        $reclamation = Reclamation::create($donnees);

        return redirect()
            ->route('admin.reclamations.show', $reclamation)
            ->with('success', "La réclamation n°{$reclamation->id} a été enregistrée.");
    }

    /**
     * Détail d'une réclamation avec le fil de ses réponses (jointure 1-N).
     */
    public function show(Reclamation $reclamation): View
    {
        $reclamation->load(['auteur', 'reponses.auteur']);

        return view('modules.reclamations.admin.reclamations.show', compact('reclamation'));
    }

    public function edit(Reclamation $reclamation): View
    {
        return view('modules.reclamations.admin.reclamations.edit', [
            'reclamation' => $reclamation,
            'membres' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function update(ReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        $donnees = $request->safe()->except('piece_jointe');

        if ($request->hasFile('piece_jointe')) {
            $this->supprimerFichier($reclamation);
            $donnees['piece_jointe'] = $this->enregistrerFichier($request->file('piece_jointe'));
        }

        $reclamation->update($donnees);

        return redirect()
            ->route('admin.reclamations.show', $reclamation)
            ->with('success', "La réclamation n°{$reclamation->id} a été modifiée.");
    }

    public function destroy(Reclamation $reclamation): RedirectResponse
    {
        $this->supprimerFichier($reclamation);
        $reclamation->delete();

        return redirect()
            ->route('admin.reclamations.index')
            ->with('success', "La réclamation n°{$reclamation->id} a été supprimée.");
    }

    private function enregistrerFichier(UploadedFile $fichier): string
    {
        $nom = Str::uuid().'.'.$fichier->getClientOriginalExtension();
        $fichier->move(public_path('uploads/reclamations'), $nom);

        return 'uploads/reclamations/'.$nom;
    }

    private function supprimerFichier(Reclamation $reclamation): void
    {
        if ($reclamation->piece_jointe && File::exists(public_path($reclamation->piece_jointe))) {
            File::delete(public_path($reclamation->piece_jointe));
        }
    }
}
