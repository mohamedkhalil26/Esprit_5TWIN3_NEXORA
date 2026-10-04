<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeders des modules, dans l'ordre d'exécution.
     * Un seeder absent (module pas encore intégré) est simplement ignoré.
     */
    protected array $moduleSeeders = [
        EquipementsModuleSeeder::class,   // Membre 1 : catégories + équipements
        LocationsModuleSeeder::class,     // Membre 2 : réservations + paiements (dépend du membre 1)
        ReclamationsModuleSeeder::class,  // Membre 3 : réclamations + réponses
        EvenementsModuleSeeder::class,    // Membre 4 : événements + participations
        BlogModuleSeeder::class,          // Membre 5 : articles + commentaires
    ];

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Comptes de démonstration (mot de passe : "password")
        User::factory()->admin()->create([
            'name' => 'Admin SolarShare',
            'email' => 'admin@solarshare.tn',
        ]);

        User::factory()->create([
            'name' => 'Client Démo',
            'email' => 'client@solarshare.tn',
        ]);

        User::factory(10)->create();

        foreach ($this->moduleSeeders as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }
    }
}
