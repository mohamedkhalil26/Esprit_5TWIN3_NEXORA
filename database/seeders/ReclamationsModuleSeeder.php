<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Point d'entrée du module 3, appelé automatiquement par DatabaseSeeder.
 */
class ReclamationsModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReclamationSeeder::class,
            ReponseReclamationSeeder::class,
        ]);
    }
}
