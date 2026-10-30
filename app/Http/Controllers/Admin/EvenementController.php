<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvenementRequest;
use App\Models\Evenement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EvenementController extends Controller
{
    public function index(Request $request): View
    {
        $evenements = Evenement::query()
            ->avecNbInscrits()
            ->filtrer($request->only(['q', 'type', 'statut', 'periode']))
            ->orderByDesc('date_debut')
            ->paginate(10)
            ->withQueryString();

        return view('modules.evenements.admin.evenements.index', compact('evenements'));
    }

    public function create(): View
    {
        return view('modules.evenements.admin.evenements.create', [
            'evenement' => new Evenement(['statut' => 'planifie', 'prix' => 0, 'capacite' => 20]),
        ]);
    }

    public function store(EvenementRequest $request): RedirectResponse
    {
        $donnees = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $donnees['image'] = $this->enregistrerImage($request->file('image'));
        }

        $evenement = Evenement::create($donnees);

        return redirect()
            ->route('admin.evenements.show', $evenement)
            ->with('success', "L'événement « {$evenement->titre} » a été créé.");
    }

    /**
     * Détail d'un événement et liste de ses participants (relation N-N).
     */
    public function show(Evenement $evenement): View
    {
        $participants = $evenement->participants()->orderBy('name')->get();

        return view('modules.evenements.admin.evenements.show', compact('evenement', 'participants'));
    }

    public function edit(Evenement $evenement): View
    {
        return view('modules.evenements.admin.evenements.edit', compact('evenement'));
    }

    public function update(EvenementRequest $request, Evenement $evenement): RedirectResponse
    {
        $donnees = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $this->supprimerImage($evenement);
            $donnees['image'] = $this->enregistrerImage($request->file('image'));
        }

        DB::transaction(function () use ($evenement, $donnees) {
            $locked = Evenement::lockForUpdate()->findOrFail($evenement->id);
            if ($donnees['capacite'] < $locked->nb_inscrits) {
                throw ValidationException::withMessages(['capacite' => 'La capacité ne peut pas être inférieure au nombre d’inscrits.']);
            }
            $locked->update($donnees);
        }, 3);

        return redirect()
            ->route('admin.evenements.show', $evenement)
            ->with('success', "L'événement « {$evenement->titre} » a été modifié.");
    }

    public function destroy(Evenement $evenement): RedirectResponse
    {
        $this->supprimerImage($evenement);
        $evenement->delete();

        return redirect()
            ->route('admin.evenements.index')
            ->with('success', "L'événement « {$evenement->titre} » et ses inscriptions ont été supprimés.");
    }

    private function enregistrerImage(UploadedFile $fichier): string
    {
        $nom = Str::uuid().'.'.$fichier->extension();
        $fichier->move(public_path('uploads/evenements'), $nom);

        return 'uploads/evenements/'.$nom;
    }

    private function supprimerImage(Evenement $evenement): void
    {
        if ($evenement->image && File::exists(public_path($evenement->image))) {
            File::delete(public_path($evenement->image));
        }
    }
}
