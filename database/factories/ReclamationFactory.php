<?php

namespace Database\Factories;

use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reclamation>
 */
class ReclamationFactory extends Factory
{
    protected $model = Reclamation::class;

    /**
     * Exemples réalistes par catégorie : [sujet, description].
     */
    public const EXEMPLES = [
        'technique' => [
            ['Impossible de modifier mon profil', 'Quand je clique sur « Enregistrer » dans mon profil, la page se recharge mais mes informations ne sont pas mises à jour. J\'ai essayé sur deux navigateurs différents.'],
            ['Le site est très lent le soir', 'Depuis quelques jours, les pages du catalogue mettent plus de dix secondes à s\'afficher entre 20h et 22h. Est-ce un problème connu ?'],
        ],
        'paiement' => [
            ['Paiement débité deux fois', 'Ma carte a été débitée deux fois pour la même réservation du panneau solaire pliable. Merci de me rembourser le montant en double.'],
            ['Caution non restituée', 'J\'ai rendu la batterie en parfait état il y a une semaine mais la caution ne m\'a toujours pas été restituée par le propriétaire.'],
        ],
        'equipement' => [
            ['Batterie qui ne tient pas la charge', 'La station électrique louée ce week-end s\'est déchargée en moins de deux heures alors que l\'annonce indiquait une capacité de 1000 Wh.'],
            ['Panneau livré avec un câble abîmé', 'Le câble de sortie du panneau solaire était dénudé à la livraison, je n\'ai pas pu l\'utiliser en toute sécurité.'],
        ],
        'comportement' => [
            ['Propriétaire injoignable', 'Le propriétaire ne répond plus à mes messages ni à mes appels alors que la location commence demain matin.'],
            ['Retard important au rendez-vous', 'Le locataire est arrivé avec plus de deux heures de retard pour récupérer l\'éolienne, sans prévenir.'],
        ],
        'autre' => [
            ['Suggestion : ajouter un calendrier', 'Ce serait pratique de voir directement sur la fiche d\'un équipement un calendrier avec les jours déjà réservés.'],
            ['Question sur l\'assurance', 'Que se passe-t-il si un équipement est endommagé par la météo pendant ma location ? Suis-je couvert ?'],
        ],
    ];

    public function definition(): array
    {
        $categorie = fake()->randomElement(array_keys(self::EXEMPLES));
        [$sujet, $description] = fake()->randomElement(self::EXEMPLES[$categorie]);

        return [
            'user_id' => User::factory(),
            'sujet' => $sujet,
            'description' => $description,
            'categorie' => $categorie,
            'priorite' => fake()->randomElement(array_keys(Reclamation::PRIORITES)),
            'statut' => fake()->randomElement(array_keys(Reclamation::STATUTS)),
            'piece_jointe' => null,
            'created_at' => fake()->dateTimeBetween('-45 days', '-1 day'),
        ];
    }

    public function ouverte(): static
    {
        return $this->state(fn () => ['statut' => 'ouverte']);
    }
}
