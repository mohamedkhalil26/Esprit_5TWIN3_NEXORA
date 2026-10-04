<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Panneaux solaires portables', 'Panneaux pliables et valises solaires pour produire de l\'électricité partout.', 'bi-sun'],
            ['Batteries & stations', 'Stations électriques et batteries pour stocker l\'énergie produite.', 'bi-battery-charging'],
            ['Petites éoliennes', 'Éoliennes domestiques et mini-éoliennes pour les sites ventés.', 'bi-wind'],
            ['Kits solaires complets', 'Ensembles prêts à l\'emploi : panneau, régulateur et batterie.', 'bi-box-seam'],
            ['Chargeurs & éclairage', 'Chargeurs USB solaires et lampes autonomes.', 'bi-lamp'],
        ];

        foreach ($categories as [$nom, $description, $icone]) {
            Categorie::firstOrCreate(['nom' => $nom], compact('description', 'icone'));
        }
    }
}
