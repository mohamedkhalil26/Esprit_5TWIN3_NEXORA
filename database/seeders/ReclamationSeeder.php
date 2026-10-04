<?php

namespace Database\Seeders;

use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReclamationSeeder extends Seeder
{
    public function run(): void
    {
        // Le compte de démo n'a qu'une réclamation (déjà traitée) pour pouvoir en déposer d'autres pendant la démo
        $clientDemo = User::where('email', 'client@solarshare.tn')->first();
        $membres = User::where('role', User::ROLE_USER)->whereKeyNot($clientDemo?->id)->get();

        if ($membres->isEmpty()) {
            $membres = User::factory(5)->create();
        }

        if ($clientDemo) {
            Reclamation::factory()->for($clientDemo, 'auteur')->create(['statut' => 'resolue']);
        }

        // Chaque réclamation est rattachée à un membre existant
        Reclamation::factory()
            ->count(13)
            ->sequence(fn () => ['user_id' => $membres->random()->id])
            ->create();
    }
}
