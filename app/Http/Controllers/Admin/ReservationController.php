<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReservationRequest;
use App\Models\Equipement;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /**
     * Liste des réservations avec équipement, locataire et total payé (jointures).
     */
    public function index(Request $request): View
    {
        $reservations = Reservation::query()
            ->with(['equipement', 'locataire'])
            ->withSum(['paiements as total_paye' => fn ($q) => $q->where('statut', 'valide')], 'montant')
            ->filtrer($request->only(['q', 'statut', 'du', 'au']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('modules.locations.admin.reservations.index', compact('reservations'));
    }

    public function create(Request $request): View
    {
        return view('modules.locations.admin.reservations.create', [
            'reservation' => new Reservation([
                'statut' => 'en_attente',
                'equipement_id' => $request->integer('equipement') ?: null,
            ]),
            ...$this->listesFormulaire(),
        ]);
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        $reservation = app(ReservationService::class)->save($request->validated());

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', "La réservation n°{$reservation->id} a été créée ({$reservation->nb_jours} jour(s)).");
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['equipement.categorie', 'locataire', 'paiements']);

        return view('modules.locations.admin.reservations.show', compact('reservation'));
    }

    public function edit(Reservation $reservation): View
    {
        return view('modules.locations.admin.reservations.edit', [
            'reservation' => $reservation,
            ...$this->listesFormulaire(),
        ]);
    }

    public function update(ReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        app(ReservationService::class)->save($request->validated(), $reservation);

        return redirect()
            ->route('admin.reservations.show', $reservation)
            ->with('success', "La réservation n°{$reservation->id} a été modifiée.");
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        $reservation->delete();

        return redirect()
            ->route('admin.reservations.index')
            ->with('success', "La réservation n°{$reservation->id} et ses paiements ont été supprimés.");
    }

    /**
     * Calcule automatiquement la durée et le montant (prix/jour × nb jours).
     */
    private function avecMontant(array $donnees): array
    {
        $equipement = Equipement::findOrFail($donnees['equipement_id']);
        $donnees['nb_jours'] = Reservation::calculerJours($donnees['date_debut'], $donnees['date_fin']);
        $donnees['montant_total'] = $donnees['nb_jours'] * $equipement->prix_jour;

        return $donnees;
    }

    private function listesFormulaire(): array
    {
        return [
            'equipements' => Equipement::with('categorie')->orderBy('nom')->get(),
            'locataires' => User::orderBy('name')->get(['id', 'name', 'email']),
        ];
    }
}
