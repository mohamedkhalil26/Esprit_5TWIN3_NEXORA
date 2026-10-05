<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Point d'entrée du module 5, appelé automatiquement par DatabaseSeeder.
 */
class BlogModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ArticleSeeder::class,
            CommentaireSeeder::class,
        ]);
    }
}
