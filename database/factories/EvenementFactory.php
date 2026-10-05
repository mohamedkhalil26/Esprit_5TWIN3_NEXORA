<?php

namespace Database\Factories;

use App\Models\Evenement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evenement>
 */
class EvenementFactory extends Factory
{
    protected $model = Evenement::class;

    public function definition(): array
    {
        $debut = fake()->dateTimeBetween('-1 month', '+2 months');
        $debut->setTime(fake()->randomElement([9, 10, 14, 17]), 0);
        $fin = (clone $debut)->modify('+'.fake()->randomElement([2, 3, 4]).' hours');

        return [
            'titre' => 'Atelier '.fake()->words(3, true),
            'description' => 'Rencontre conviviale autour des énergies renouvelables : démonstrations, échanges entre membres et conseils pratiques pour réduire sa consommation.',
            'type' => fake()->randomElement(array_keys(Evenement::TYPES)),
            'lieu' => fake()->randomElement(['Maison des jeunes', 'Espace coworking Lac 2', 'Parc du Belvédère', 'Technopole El Ghazala']),
            'ville' => fake()->randomElement(['Tunis', 'Ariana', 'Sousse', 'Sfax']),
            'date_debut' => $debut,
            'date_fin' => $fin,
            'capacite' => fake()->randomElement([15, 20, 30, 50]),
            'prix' => fake()->randomElement([0, 0, 10, 20]),
            'image' => null,
            'statut' => $fin < now() ? 'termine' : 'planifie',
        ];
    }
}
