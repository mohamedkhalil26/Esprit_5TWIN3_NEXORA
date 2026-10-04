<?php

namespace Database\Seeders;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class PaiementSeeder extends Seeder
{
    public function run(): void
    {
        $reservations = Reservation::whereIn('statut', ['confirmee', 'en_cours', 'terminee'])->get();

        foreach ($reservations as $reservation) {
            $total = (float) $reservation->montant_total;

            if ($reservation->statut === 'terminee' || fake()->boolean()) {
                // Paiement en une fois
                Paiement::factory()->for($reservation)->create([
                    'montant' => $total,
                    'date_paiement' => $reservation->date_debut->copy()->subDay(),
                ]);
            } else {
                // Acompte de 30 % validé + solde en attente
                $acompte = round($total * 0.3, 2);

                Paiement::factory()->for($reservation)->create([
                    'montant' => $acompte,
                    'date_paiement' => $reservation->created_at,
                ]);

                Paiement::factory()->for($reservation)->enAttente()->create([
                    'montant' => $total - $acompte,
                    'date_paiement' => now(),
                ]);
            }
        }
    }
}
