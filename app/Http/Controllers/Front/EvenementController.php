<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Evenement;
use App\Models\Participation;
use App\Services\Ai\Notifications\SmartNotificationService;
use App\Services\ParticipationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvenementController extends Controller
{
    public function index(Request $request): View
    {
        $evenements = Evenement::query()
            ->avecNbInscrits()
            ->aVenir()
            ->filtrer($request->only(['q', 'type', 'ville']))
            ->orderBy('date_debut')
            ->paginate(6)
            ->withQueryString();

        return view('modules.evenements.front.index', compact('evenements'));
    }

    public function show(Request $request, Evenement $evenement): View
    {
        $evenement->loadCount(['participations as participations_actives_count' => fn ($q) => $q->where('statut', '!=', 'annule')]);

        $maParticipation = $request->user()
            ? $evenement->participations()->where('user_id', $request->user()->id)->first()
            : null;

        return view('modules.evenements.front.show', compact('evenement', 'maParticipation'));
    }

    public function inscrire(Request $request, Evenement $evenement): RedirectResponse
    {
        $request->validate(
            ['commentaire' => ['nullable', 'string', 'max:500']],
            [],
            ['commentaire' => 'commentaire']
        );

        app(ParticipationService::class)->register($evenement, $request->user()->id, $request->input('commentaire'));
        app(SmartNotificationService::class)->create($request->user(), 'event', 'Inscription confirmée', 'Votre inscription à « '.$evenement->titre.' » est enregistrée.', 'event:'.$evenement->id.':user:'.$request->user()->id.':registered', $evenement);

        return back()->with('success', 'Votre inscription est confirmée. À bientôt !');
    }

    public function desinscrire(Request $request, Evenement $evenement): RedirectResponse
    {
        $evenement->participations()
            ->where('user_id', $request->user()->id)
            ->update(['statut' => 'annule']);

        return back()->with('success', 'Votre inscription a été annulée.');
    }

    public function mesEvenements(Request $request): View
    {
        $participations = Participation::with('evenement')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('modules.evenements.front.mes-evenements', compact('participations'));
    }
}
