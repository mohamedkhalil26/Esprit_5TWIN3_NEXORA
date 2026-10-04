<?php

namespace Database\Seeders;

use App\Models\Equipement;
use Illuminate\Database\Seeder;

/**
 * Point d'entrée du module 2, appelé automatiquement par DatabaseSeeder.
 * Dépend des équipements du module 1.
 */
class LocationsModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(Equipement::class) || Equipement::count() === 0) {
            $this->command?->warn('Module Locations : aucun équipement trouvé, intégrez d\'abord le module Équipements.');

            return;
        }

        $this->call([
            ReservationSeeder::class,
            PaiementSeeder::class,
        ]);
    }
}
