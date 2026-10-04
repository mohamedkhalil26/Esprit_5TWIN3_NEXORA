<?php

namespace Database\Factories;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Paiement>
 */
class PaiementFactory extends Factory
{
    protected $model = Paiement::class;

    public function definition(): array
    {
        return [
            'reservation_id' => Reservation::factory(),
            'montant' => fake()->randomFloat(2, 10, 150),
            'methode' => fake()->randomElement(array_keys(Paiement::METHODES)),
            'statut' => 'valide',
            'reference' => null, // générée automatiquement par le modèle
            'date_paiement' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }

    public function enAttente(): static
    {
        return $this->state(fn () => ['statut' => 'en_attente']);
    }
}
