<?php

namespace Database\Factories;

use App\Models\Reclamation;
use App\Models\ReponseReclamation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReponseReclamation>
 */
class ReponseReclamationFactory extends Factory
{
    protected $model = ReponseReclamation::class;

    public const MESSAGES_ADMIN = [
        'Bonjour, merci pour votre signalement. Nous avons bien pris en compte votre demande et revenons vers vous sous 48 heures.',
        'Nous avons contacté l\'autre membre concerné afin de trouver une solution rapidement.',
        'Le remboursement a été effectué, il apparaîtra sur votre compte d\'ici 3 à 5 jours ouvrés.',
        'Après vérification, le problème est corrigé. N\'hésitez pas à nous recontacter si besoin.',
    ];

    public const MESSAGES_MEMBRE = [
        'Merci pour votre réponse rapide !',
        'Toujours pas de nouvelles de mon côté, pouvez-vous relancer ?',
        'C\'est réglé, merci beaucoup pour votre aide.',
    ];

    public function definition(): array
    {
        return [
            'reclamation_id' => Reclamation::factory(),
            'user_id' => User::factory(),
            'message' => fake()->randomElement(self::MESSAGES_ADMIN),
        ];
    }

    public function duMembre(): static
    {
        return $this->state(fn () => ['message' => fake()->randomElement(self::MESSAGES_MEMBRE)]);
    }
}
