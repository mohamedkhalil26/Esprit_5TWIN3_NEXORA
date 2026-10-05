<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Commentaire;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commentaire>
 */
class CommentaireFactory extends Factory
{
    protected $model = Commentaire::class;

    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'contenu' => fake()->randomElement([
                'Merci pour cet article très clair, j\'ai enfin compris comment dimensionner mon installation !',
                'Super conseils, je vais tester l\'orientation à 30° dès ce week-end.',
                'Est-ce que ces recommandations s\'appliquent aussi aux panneaux souples ?',
                'J\'ai loué une station électrique sur SolarShare le mois dernier, expérience parfaite.',
                'Article intéressant, mais il manque un comparatif des prix des batteries.',
                'Je confirme pour la poussière : après nettoyage ma production a nettement augmenté.',
                'Très utile pour les débutants comme moi, merci !',
            ]),
            'approuve' => true,
            'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }

    public function enAttente(): static
    {
        return $this->state(fn () => ['approuve' => false]);
    }
}
