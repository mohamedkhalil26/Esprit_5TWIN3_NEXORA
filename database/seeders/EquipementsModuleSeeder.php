<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Point d'entrée du module 1, appelé automatiquement par DatabaseSeeder.
 */
class EquipementsModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorieSeeder::class,
            EquipementSeeder::class,
        ]);
    }
}
