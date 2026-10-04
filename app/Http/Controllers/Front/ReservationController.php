<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReservationRequest;
use App\Models\Equipement;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace "Mes réservations" du locataire connecté.
 */
class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $reservations = Reservation::with(['equipement.categorie', 'paiements'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(8);

        return view('modules.locations.front.index', compact('reservations'));
    }

    public function create(Request $request): View
    {
        $equipement = Equipement::with(['categorie', 'proprietaire'])->findOrFail($request->integer('equipement'));

        // Périodes déjà réservées, affichées pour aider le locataire
        $periodesOccupees = Reservation::where('equipement_id', $equipement->id)
            ->whereIn('statut', Reservation::STATUTS_ACTIFS)
            ->whereDate('date_fin', '>=', today())
            ->orderBy('date_debut')
            ->get(['date_debut', 'date_fin']);

        return view('modules.locations.front.create', compact('equipement', 'periodesOccupees'));
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        $equipement = Equipement::findOrFail($request->validated('equipement_id'));
        $jours = Reservation::calculerJours($request->validated('date_debut'), $request->validated('date_fin'));

        $reservation = Reservation::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'nb_jours' => $jours,
            'montant_total' => $jours * $equipement->prix_jour,
            'statut' => 'en_attente',
        ]);

        return redirect()
            ->route('front.reservations.show', $reservation)
            ->with('success', 'Votre demande de réservation a été envoyée au propriétaire.');
    }

    public function show(Request $request, Reservation $reservation): View
    {
        abort_unless((int) $reservation->user_id === $request->user()->id, 403);

        $reservation->load(['equipement.categorie', 'equipement.proprietaire', 'paiements']);

        return view('modules.locations.front.show', compact('reservation'));
    }

    public function annuler(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_unless((int) $reservation->user_id === $request->user()->id, 403);

        if (! in_array($reservation->statut, ['en_attente', 'confirmee'], true)) {
            return back()->with('error', 'Cette réservation ne peut plus être annulée.');
        }

        $reservation->update(['statut' => 'annulee']);

        return back()->with('success', 'Votre réservation a été annulée.');
    }
}
