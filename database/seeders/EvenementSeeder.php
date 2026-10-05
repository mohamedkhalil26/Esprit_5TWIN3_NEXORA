<?php

namespace Database\Seeders;

use App\Models\Evenement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EvenementSeeder extends Seeder
{
    public function run(): void
    {
        // [titre, type, lieu, ville, décalage en jours, heure, durée (h), capacité, prix, description]
        $evenements = [
            ['Installer son premier panneau solaire portable', 'atelier', 'Espace coworking Lac 2', 'Tunis', 6, 10, 3, 15, 0,
                'Atelier pratique pour apprendre à brancher un panneau pliable sur une station électrique, orienter correctement le panneau et lire les indicateurs de charge.'],
            ['Comprendre sa facture d\'électricité', 'formation', 'Maison des jeunes', 'Ariana', 12, 18, 2, 30, 0,
                'Formation pour décrypter sa facture STEG, identifier les appareils les plus énergivores et estimer les économies possibles grâce au solaire.'],
            ['Conférence : l\'avenir des énergies renouvelables en Tunisie', 'conference', 'Cité des sciences', 'Tunis', 20, 17, 2, 120, 0,
                'Des experts du secteur présentent les projets solaires et éoliens en cours dans le pays et les opportunités pour les particuliers.'],
            ['Sortie découverte : le parc éolien de Sidi Daoud', 'sortie', 'Parc éolien de Sidi Daoud', 'Nabeul', 27, 9, 6, 25, 20,
                'Visite guidée d\'un parc éolien avec explication du fonctionnement des turbines, suivie d\'un pique-nique alimenté 100 % solaire.'],
            ['Batteries et stockage : bien choisir', 'formation', 'Technopole El Ghazala', 'Ariana', 35, 14, 3, 20, 10,
                'Lithium, plomb, stations tout-en-un : comparatif des solutions de stockage, durée de vie et bonnes pratiques de recharge.'],
            ['Réparer ses lampes solaires de jardin', 'atelier', 'Repair café', 'Sousse', 45, 15, 2, 12, 0,
                'Venez avec vos lampes solaires en panne : nous apprendrons ensemble à remplacer la batterie et nettoyer les cellules.'],
            ['Rencontre des membres SolarShare', 'sortie', 'Parc du Belvédère', 'Tunis', -10, 16, 3, 40, 0,
                'Première rencontre de la communauté : échanges d\'expérience entre propriétaires et locataires d\'équipements.'],
            ['Initiation au dimensionnement solaire', 'formation', 'Maison des jeunes', 'Sfax', -25, 10, 4, 20, 15,
                'Calculer ses besoins en Wh/jour et choisir la puissance de panneau et la capacité de batterie adaptées.'],
        ];

        foreach ($evenements as [$titre, $type, $lieu, $ville, $jours, $heure, $duree, $capacite, $prix, $description]) {
            $debut = Carbon::today()->addDays($jours)->setTime($heure, 0);

            Evenement::factory()->create([
                'titre' => $titre,
                'type' => $type,
                'lieu' => $lieu,
                'ville' => $ville,
                'description' => $description,
                'date_debut' => $debut,
                'date_fin' => $debut->copy()->addHours($duree),
                'capacite' => $capacite,
                'prix' => $prix,
                'statut' => $jours < 0 ? 'termine' : 'planifie',
            ]);
        }
    }
}
