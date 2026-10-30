<?php

namespace Database\Factories;

use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipement>
 */
class EquipementFactory extends Factory
{
    protected $model = Equipement::class;

    /**
     * Modèles d'équipements réalistes : [nom, marque, puissance W, capacité Wh, prix/jour].
     */
    public const MODELES = [
        ['Panneau solaire pliable 100 W', 'Jackery', 100, null, 12],
        ['Panneau solaire portable 200 W', 'EcoFlow', 200, null, 18],
        ['Valise solaire 120 W', 'Bluetti', 120, null, 14],
        ['Station électrique River 2', 'EcoFlow', 300, 256, 20],
        ['Batterie nomade Explorer 1000', 'Jackery', 1000, 1002, 35],
        ['Batterie lithium 100 Ah', 'Victron', null, 1280, 22],
        ['Mini éolienne 400 W', 'Sunforce', 400, null, 25],
        ['Éolienne verticale 300 W', 'Pikasola', 300, null, 22],
        ['Kit solaire camping-car 200 W', 'Victron', 200, 1200, 30],
        ['Lampe solaire de jardin (lot de 6)', 'Philips', 5, 20, 5],
        ['Chargeur solaire USB 28 W', 'Anker', 28, null, 4],
        ['Générateur solaire AC180', 'Bluetti', 1800, 1152, 40],
    ];

    public const DESCRIPTIONS = [
        'Équipement en parfait état de fonctionnement, livré avec ses câbles et sa housse de transport. Idéal pour le camping, les week-ends en van ou les coupures de courant.',
        "Très facile à installer : il suffit de le déplier et de l'orienter vers le soleil. Je fournis une notice d'utilisation simple et je reste disponible pour toute question.",
        'Utilisé seulement quelques fois par an, je préfère le partager plutôt que de le laisser dormir dans le garage. Compatible avec la plupart des stations électriques portables.',
        "Parfait pour alimenter un petit réfrigérateur, des lampes et recharger téléphones et ordinateurs lors d'un événement en plein air ou d'un marché.",
        'Matériel fiable et silencieux, testé avant chaque location. Remise en main propre avec démonstration, possibilité de livraison dans la ville moyennant un petit supplément.',
    ];

    public function definition(): array
    {
        [$nom, $marque, $puissance, $capacite, $prix] = fake()->randomElement(self::MODELES);

        return [
            'categorie_id' => Categorie::factory(),
            'user_id' => User::factory(),
            'nom' => $nom,
            'marque' => $marque,
            'description' => fake()->randomElement(self::DESCRIPTIONS),
            'puissance_watts' => $puissance,
            'capacite_wh' => $capacite,
            'prix_jour' => $prix,
            'caution' => $prix * fake()->numberBetween(5, 10),
            'etat' => fake()->randomElement(array_keys(Equipement::ETATS)),
            'ville' => fake()->randomElement(['Tunis', 'Ariana', 'Sousse', 'Sfax', 'Nabeul', 'Bizerte', 'Monastir']),
            'disponible' => fake()->boolean(85),
            'image' => null,
        ];
    }

    public function indisponible(): static
    {
        return $this->state(fn () => ['disponible' => false]);
    }
}
