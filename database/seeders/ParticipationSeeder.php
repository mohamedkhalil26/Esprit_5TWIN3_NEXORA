<?php

namespace Database\Seeders;

use App\Models\Evenement;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ParticipationSeeder extends Seeder
{
    public function run(): void
    {
        $membres = User::where('role', User::ROLE_USER)->get();

        foreach (Evenement::all() as $evenement) {
            $nombre = min($membres->count(), fake()->numberBetween(3, (int) min(10, $evenement->capacite)));

            // Membres distincts pour respecter l'unicité (événement, membre)
            foreach ($membres->random($nombre) as $membre) {
                Participation::factory()
                    ->for($evenement)
                    ->for($membre, 'participant')
                    ->create([
                        'statut' => $evenement->statut === 'termine'
                            ? fake()->randomElement(['present', 'present', 'present', 'absent'])
                            : fake()->randomElement(['inscrit', 'inscrit', 'inscrit', 'annule']),
                    ]);
            }
        }
    }
}
