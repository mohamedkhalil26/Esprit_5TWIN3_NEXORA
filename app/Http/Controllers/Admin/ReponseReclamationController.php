<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReponseReclamationRequest;
use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReponseReclamationController extends Controller
{
    /**
     * Toutes les réponses avec leur réclamation et leur auteur (jointures).
     */
    public function index(Request $request): View
    {
        $reponses = ReponseReclamation::query()
            ->with(['reclamation', 'auteur'])
            ->when($request->input('q'), fn ($query, $q) => $query->where('message', 'like', "%{$q}%"))
            ->when($request->input('reclamation'), fn ($query, $id) => $query->where('reclamation_id', $id))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.reclamations.admin.reponses.index', [
            'reponses' => $reponses,
            'reclamations' => Reclamation::orderByDesc('id')->get(['id', 'sujet']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('modules.reclamations.admin.reponses.create', [
            'reponse' => new ReponseReclamation(['reclamation_id' => $request->integer('reclamation') ?: null]),
            'reclamations' => Reclamation::with('auteur')->orderByDesc('id')->get(),
        ]);
    }

    public function store(ReponseReclamationRequest $request): RedirectResponse
    {
        $reponse = ReponseReclamation::create([
            'reclamation_id' => $request->validated('reclamation_id'),
            'message' => $request->validated('message'),
            'user_id' => $request->user()->id,
        ]);

        $this->changerStatut($reponse->reclamation, $request->validated('nouveau_statut'));

        return redirect()
            ->route('admin.reclamations.show', $reponse->reclamation_id)
            ->with('success', 'Votre réponse a été envoyée au membre.');
    }

    public function show(ReponseReclamation $reponse): View
    {
        $reponse->load(['reclamation.auteur', 'auteur']);

        return view('modules.reclamations.admin.reponses.show', compact('reponse'));
    }

    public function edit(ReponseReclamation $reponse): View
    {
        return view('modules.reclamations.admin.reponses.edit', [
            'reponse' => $reponse,
            'reclamations' => Reclamation::with('auteur')->orderByDesc('id')->get(),
        ]);
    }

    public function update(ReponseReclamationRequest $request, ReponseReclamation $reponse): RedirectResponse
    {
        $reponse->update($request->safe()->only(['reclamation_id', 'message']));

        $this->changerStatut($reponse->reclamation, $request->validated('nouveau_statut'));

        return redirect()
            ->route('admin.reponses.show', $reponse)
            ->with('success', 'La réponse a été modifiée.');
    }

    public function destroy(ReponseReclamation $reponse): RedirectResponse
    {
        $reclamationId = $reponse->reclamation_id;
        $reponse->delete();

        return redirect()
            ->route('admin.reclamations.show', $reclamationId)
            ->with('success', 'La réponse a été supprimée.');
    }

    private function changerStatut(Reclamation $reclamation, ?string $statut): void
    {
        if ($statut) {
            $reclamation->update(['statut' => $statut]);
        } elseif ($reclamation->statut === 'ouverte') {
            // Première réponse de l'équipe : la réclamation passe "en cours"
            $reclamation->update(['statut' => 'en_cours']);
        }
    }
}
