<?php

namespace Database\Seeders;

use App\Models\Equipement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        $locataires = User::where('role', User::ROLE_USER)->get();

        foreach (Equipement::inRandomOrder()->take(10)->get() as $equipement) {
            // Périodes successives sans chevauchement pour un même équipement
            $debut = Carbon::today()->subDays(fake()->numberBetween(30, 60));

            for ($i = 0; $i < fake()->numberBetween(1, 3); $i++) {
                $jours = fake()->numberBetween(2, 6);
                $fin = $debut->copy()->addDays($jours - 1);

                Reservation::factory()
                    ->for($equipement)
                    ->for($locataires->where('id', '!=', $equipement->user_id)->random(), 'locataire')
                    ->create([
                        'date_debut' => $debut->toDateString(),
                        'date_fin' => $fin->toDateString(),
                        'nb_jours' => $jours,
                        'statut' => $this->statutSelonDates($debut, $fin),
                    ]);

                $debut = $fin->copy()->addDays(fake()->numberBetween(3, 15));
            }
        }
    }

    private function statutSelonDates(Carbon $debut, Carbon $fin): string
    {
        if ($fin->isPast()) {
            return fake()->randomElement(['terminee', 'terminee', 'annulee']);
        }

        if ($debut->isPast()) {
            return 'en_cours';
        }

        return fake()->randomElement(['en_attente', 'confirmee']);
    }
}
