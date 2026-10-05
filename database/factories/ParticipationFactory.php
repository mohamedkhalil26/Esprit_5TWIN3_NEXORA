<?php

namespace Database\Factories;

use App\Models\Evenement;
use App\Models\Participation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participation>
 */
class ParticipationFactory extends Factory
{
    protected $model = Participation::class;

    public function definition(): array
    {
        return [
            'evenement_id' => Evenement::factory(),
            'user_id' => User::factory(),
            'statut' => 'inscrit',
            'commentaire' => fake()->optional(0.3)->randomElement([
                'J\'ai hâte d\'y participer !',
                'Je viendrai avec un ami.',
                'Est-ce adapté aux débutants ?',
                'Je peux apporter mon propre panneau pour la démonstration.',
            ]),
        ];
    }
}
