<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Commentaire;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentaireSeeder extends Seeder
{
    public function run(): void
    {
        $membres = User::where('role', User::ROLE_USER)->get();

        foreach (Article::where('statut', 'publie')->get() as $article) {
            for ($i = 0; $i < fake()->numberBetween(2, 5); $i++) {
                $factory = Commentaire::factory()
                    ->for($article)
                    ->for($membres->random(), 'auteur');

                // Environ un commentaire sur six attend la modération
                (fake()->boolean(15) ? $factory->enAttente() : $factory)->create([
                    'created_at' => fake()->dateTimeBetween($article->publie_le, 'now'),
                ]);
            }
        }
    }
}
