<?php

namespace App\Services;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function save(array $data, ?Paiement $payment = null): Paiement
    {
        return DB::transaction(function () use ($data, $payment) {
            $reservation = Reservation::lockForUpdate()->findOrFail($data['reservation_id']);
            if ($data['statut'] === 'valide') {
                $paid = $reservation->paiements()->where('statut', 'valide')->when($payment, fn ($q) => $q->whereKeyNot($payment->id))->sum('montant');
                if ($reservation->statut === 'annulee' || round((float) $data['montant'] + $paid, 2) > (float) $reservation->montant_total) {
                    throw ValidationException::withMessages(['montant' => 'Paiement impossible : réservation annulée ou solde dépassé.']);
                }
            }
            if ($payment) {
                $payment->update($data);

                return $payment;
            }

            return Paiement::create($data);
        }, 3);
    }
}
