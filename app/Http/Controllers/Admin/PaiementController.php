<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaiementRequest;
use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaiementController extends Controller
{
    public function index(Request $request): View
    {
        $paiements = Paiement::query()
            ->with(['reservation.equipement', 'reservation.locataire'])
            ->when($request->input('q'), function ($query, $q) {
                $query->where(function ($sous) use ($q) {
                    $sous->where('reference', 'like', "%{$q}%")
                        ->orWhereHas('reservation.locataire', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($request->input('methode'), fn ($query, $methode) => $query->where('methode', $methode))
            ->when($request->input('statut'), fn ($query, $statut) => $query->where('statut', $statut))
            ->latest('date_paiement')
            ->paginate(10)
            ->withQueryString();

        $totalValide = Paiement::where('statut', 'valide')->sum('montant');

        return view('modules.locations.admin.paiements.index', compact('paiements', 'totalValide'));
    }

    public function create(Request $request): View
    {
        $reservation = Reservation::with('paiements')->find($request->integer('reservation'));

        return view('modules.locations.admin.paiements.create', [
            'paiement' => new Paiement([
                'reservation_id' => $reservation?->id,
                'montant' => $reservation?->reste_a_payer,
                'statut' => 'valide',
                'methode' => 'carte',
                'date_paiement' => now(),
            ]),
            'reservations' => $this->reservationsSelectionnables(),
        ]);
    }

    public function store(PaiementRequest $request): RedirectResponse
    {
        $paiement = Paiement::create($request->validated());

        return redirect()
            ->route('admin.reservations.show', $paiement->reservation_id)
            ->with('success', "Le paiement {$paiement->reference} a été enregistré.");
    }

    public function show(Paiement $paiement): View
    {
        $paiement->load(['reservation.equipement', 'reservation.locataire', 'reservation.paiements']);

        return view('modules.locations.admin.paiements.show', compact('paiement'));
    }

    public function edit(Paiement $paiement): View
    {
        return view('modules.locations.admin.paiements.edit', [
            'paiement' => $paiement,
            'reservations' => $this->reservationsSelectionnables(),
        ]);
    }

    public function update(PaiementRequest $request, Paiement $paiement): RedirectResponse
    {
        $paiement->update($request->validated());

        return redirect()
            ->route('admin.paiements.show', $paiement)
            ->with('success', "Le paiement {$paiement->reference} a été modifié.");
    }

    public function destroy(Paiement $paiement): RedirectResponse
    {
        $paiement->delete();

        return redirect()
            ->route('admin.paiements.index')
            ->with('success', "Le paiement {$paiement->reference} a été supprimé.");
    }

    private function reservationsSelectionnables()
    {
        return Reservation::with(['equipement', 'locataire', 'paiements'])
            ->latest()
            ->get();
    }
}
