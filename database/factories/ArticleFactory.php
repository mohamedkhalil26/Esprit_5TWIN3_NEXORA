<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $paragraphes = collect(range(1, 4))
            ->map(fn () => fake()->randomElement(self::PARAGRAPHES))
            ->implode("\n\n");

        return [
            'user_id' => User::factory(),
            'titre' => 'Conseil énergie n°'.fake()->unique()->numberBetween(1, 9999),
            'resume' => 'Des conseils simples et concrets pour mieux utiliser les énergies renouvelables au quotidien et réduire sa facture.',
            'contenu' => $paragraphes,
            'categorie' => fake()->randomElement(array_keys(Article::CATEGORIES)),
            'image' => null,
            'statut' => 'publie',
            'publie_le' => fake()->dateTimeBetween('-3 months', 'now'),
            'vues' => fake()->numberBetween(10, 800),
        ];
    }

    public function brouillon(): static
    {
        return $this->state(fn () => ['statut' => 'brouillon', 'publie_le' => null, 'vues' => 0]);
    }

    public const PARAGRAPHES = [
        "Avant toute chose, il est essentiel de connaître ses besoins. Faites la liste des appareils que vous souhaitez alimenter, notez leur puissance en watts et le nombre d'heures d'utilisation par jour. En multipliant les deux, vous obtenez une consommation en wattheures qui servira de base pour choisir le bon équipement.",
        "L'orientation du panneau joue un rôle déterminant dans sa production. En Tunisie, un panneau orienté plein sud avec une inclinaison d'environ 30 degrés offre un excellent rendement sur l'année. Pensez aussi à nettoyer régulièrement la surface : la poussière peut faire perdre jusqu'à 20 % de production.",
        "Les batteries lithium ont largement remplacé les modèles au plomb dans les stations portables. Elles sont plus légères, supportent davantage de cycles de charge et peuvent être déchargées plus profondément sans s'abîmer. Évitez toutefois de les laisser en plein soleil : la chaleur excessive réduit leur durée de vie.",
        "Louer plutôt qu'acheter est souvent la solution la plus raisonnable pour un usage ponctuel. Un week-end de camping, un événement en plein air ou une coupure de courant prévue ne justifient pas toujours un investissement de plusieurs centaines de dinars. Le partage permet aussi de réduire l'impact environnemental de la fabrication.",
        "Enfin, n'oubliez pas que la meilleure énergie reste celle que l'on ne consomme pas. Éteindre les appareils en veille, privilégier les ampoules LED et utiliser les appareils gourmands aux heures les plus ensoleillées sont des gestes simples qui complètent parfaitement une installation solaire.",
    ];
}
