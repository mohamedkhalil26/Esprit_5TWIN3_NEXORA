<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReclamationRequest;
use App\Http\Requests\ReponseReclamationRequest;
use App\Models\Reclamation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace "Mes réclamations" du membre connecté.
 */
class ReclamationController extends Controller
{
    public function index(Request $request): View
    {
        $reclamations = Reclamation::withCount('reponses')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(8);

        return view('modules.reclamations.front.index', compact('reclamations'));
    }

    public function create(): View
    {
        return view('modules.reclamations.front.create');
    }

    public function store(ReclamationRequest $request): RedirectResponse
    {
        $donnees = $request->safe()->except('piece_jointe');

        if ($request->hasFile('piece_jointe')) {
            $donnees['piece_jointe'] = $request->file('piece_jointe')->store('reclamations', 'local');
        }

        $reclamation = Reclamation::create([
            ...$donnees,
            'user_id' => $request->user()->id,
            'statut' => 'ouverte',
        ]);

        return redirect()
            ->route('front.reclamations.show', $reclamation)
            ->with('success', 'Votre réclamation a bien été envoyée. Notre équipe vous répondra rapidement.');
    }

    public function show(Request $request, Reclamation $reclamation): View
    {
        abort_unless((int) $reclamation->user_id === $request->user()->id, 403);

        $reclamation->load('reponses.auteur');

        return view('modules.reclamations.front.show', compact('reclamation'));
    }

    public function repondre(ReponseReclamationRequest $request, Reclamation $reclamation): RedirectResponse
    {
        abort_unless((int) $reclamation->user_id === $request->user()->id, 403);

        $reclamation->reponses()->create([
            'user_id' => $request->user()->id,
            'message' => $request->validated('message'),
        ]);

        // Le membre relance : la réclamation résolue est rouverte
        if ($reclamation->statut === 'resolue') {
            $reclamation->update(['statut' => 'en_cours']);
        }

        return back()->with('success', 'Votre message a été ajouté.');
    }
}
