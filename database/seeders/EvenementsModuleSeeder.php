<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Point d'entrée du module 4, appelé automatiquement par DatabaseSeeder.
 */
class EvenementsModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EvenementSeeder::class,
            ParticipationSeeder::class,
        ]);
    }
}
