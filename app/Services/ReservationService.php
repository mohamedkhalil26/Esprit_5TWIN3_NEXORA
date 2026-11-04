<?php

namespace App\Services;

use App\Models\Equipement;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function save(array $data, ?Reservation $reservation = null): Reservation
    {
        return DB::transaction(function () use ($data, $reservation) {
            $equipment = Equipement::lockForUpdate()->findOrFail($data['equipement_id']);
            if ($reservation) {
                $reservation = Reservation::lockForUpdate()->findOrFail($reservation->id);
            }
            $active = in_array($data['statut'] ?? 'en_attente', Reservation::STATUTS_ACTIFS, true);
            if ($active && (! $equipment->disponible || Reservation::chevauchant($equipment->id, $data['date_debut'], $data['date_fin'], $reservation?->id)->exists())) {
                throw ValidationException::withMessages(['date_debut' => 'Cet équipement n’est plus disponible pour cette période.']);
            }
            $days = Reservation::calculerJours($data['date_debut'], $data['date_fin']);
            $data['nb_jours'] = $days;
            $data['montant_total'] = round($days * $equipment->prix_jour, 2);
            if ($reservation && $reservation->paiements()->where('statut', 'valide')->sum('montant') > $data['montant_total']) {
                throw ValidationException::withMessages(['date_fin' => 'Le montant ne peut pas devenir inférieur aux paiements déjà validés.']);
            }
            if ($reservation) {
                $reservation->update($data);

                return $reservation;
            }

            return Reservation::create($data);
        }, 3);
    }
}
