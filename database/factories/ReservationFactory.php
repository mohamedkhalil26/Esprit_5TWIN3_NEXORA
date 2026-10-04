<?php

namespace Database\Factories;

use App\Models\Equipement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $debut = fake()->dateTimeBetween('-2 months', '+1 month');
        $jours = fake()->numberBetween(1, 7);
        $fin = (clone $debut)->modify('+'.($jours - 1).' days');

        return [
            'equipement_id' => Equipement::factory(),
            'user_id' => User::factory(),
            'date_debut' => $debut->format('Y-m-d'),
            'date_fin' => $fin->format('Y-m-d'),
            'nb_jours' => $jours,
            'montant_total' => 0, // calculé dans configure() à partir du prix de l'équipement
            'statut' => fake()->randomElement(array_keys(Reservation::STATUTS)),
            'message' => fake()->optional(0.7)->randomElement([
                'Bonjour, je souhaite l\'utiliser pour un week-end de camping en famille.',
                'Besoin d\'électricité pour un stand lors d\'un marché artisanal.',
                'Je voudrais tester ce type d\'équipement avant d\'en acheter un.',
                'Pour faire face aux coupures de courant prévues dans mon quartier.',
                'Est-il possible de récupérer le matériel la veille au soir ?',
            ]),
        ];
    }

    /**
     * Le montant dépend de l'équipement lié : prix/jour × nombre de jours.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Reservation $reservation) {
            $reservation->montant_total = $reservation->nb_jours * $reservation->equipement->prix_jour;
        });
    }

    public function statut(string $statut): static
    {
        return $this->state(fn () => ['statut' => $statut]);
    }
}
