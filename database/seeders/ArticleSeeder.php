<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $auteurs = User::where('role', User::ROLE_ADMIN)->get()
            ->merge(User::where('role', User::ROLE_USER)->take(2)->get());

        // [titre, catégorie, résumé, brouillon ?]
        $articles = [
            ['Bien choisir son panneau solaire portable', 'solaire',
                'Puissance, poids, type de cellules : les critères essentiels pour choisir le panneau solaire portable adapté à vos besoins.', false],
            ['Station électrique ou batterie classique : que choisir ?', 'stockage',
                'Comparatif des solutions de stockage pour le camping, le télétravail en plein air et les coupures de courant.', false],
            ['Une mini-éolienne en ville, est-ce vraiment utile ?', 'eolien',
                'Vent, bruit, réglementation : ce qu\'il faut savoir avant d\'installer une petite éolienne sur un toit ou un balcon.', false],
            ['10 gestes simples pour réduire sa facture d\'électricité', 'economies',
                'Avant de produire votre propre énergie, commencez par moins consommer grâce à ces habitudes faciles à adopter.', false],
            ['SolarShare franchit le cap des 100 équipements partagés', 'actualites',
                'La communauté grandit : retour sur les premiers mois de la plateforme et les projets à venir.', false],
            ['Entretenir ses batteries lithium pour les faire durer', 'stockage',
                'Température, cycles de charge, stockage hivernal : nos conseils pour prolonger la vie de vos batteries.', false],
            ['Guide : alimenter un food-truck à l\'énergie solaire', 'solaire',
                'Brouillon en cours de rédaction sur le dimensionnement d\'une installation mobile professionnelle.', true],
        ];

        foreach ($articles as [$titre, $categorie, $resume, $brouillon]) {
            $factory = Article::factory()->for($auteurs->random(), 'auteur');

            ($brouillon ? $factory->brouillon() : $factory)->create([
                'titre' => $titre,
                'categorie' => $categorie,
                'resume' => $resume,
            ]);
        }
    }
}
