<?php

namespace Database\Seeders;

use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReponseReclamationSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::factory()->admin()->create();

        // Les réclamations déjà prises en charge ont un échange admin / membre
        foreach (Reclamation::where('statut', '!=', 'ouverte')->get() as $reclamation) {
            $date = $reclamation->created_at->copy();

            ReponseReclamation::factory()
                ->for($reclamation)
                ->for($admin, 'auteur')
                ->create(['created_at' => $date->addHours(fake()->numberBetween(2, 30))]);

            if (fake()->boolean(60)) {
                ReponseReclamation::factory()
                    ->duMembre()
                    ->for($reclamation)
                    ->for($reclamation->auteur, 'auteur')
                    ->create(['created_at' => $date->addHours(fake()->numberBetween(2, 24))]);
            }
        }
    }
}
