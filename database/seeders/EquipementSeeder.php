<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\User;
use Database\Factories\EquipementFactory;
use Illuminate\Database\Seeder;

class EquipementSeeder extends Seeder
{
    /**
     * Index des modèles (EquipementFactory::MODELES) à utiliser pour chaque catégorie.
     */
    private const MODELES_PAR_CATEGORIE = [
        'Panneaux solaires portables' => [0, 1, 2],
        'Batteries & stations' => [3, 4, 5, 11],
        'Petites éoliennes' => [6, 7],
        'Kits solaires complets' => [8],
        'Chargeurs & éclairage' => [9, 10],
    ];

    public function run(): void
    {
        $proprietaires = User::where('role', User::ROLE_USER)->get();

        if ($proprietaires->isEmpty()) {
            $proprietaires = User::factory(5)->create();
        }

        foreach (Categorie::all() as $categorie) {
            $indexModeles = self::MODELES_PAR_CATEGORIE[$categorie->nom] ?? [array_rand(EquipementFactory::MODELES)];

            foreach ($indexModeles as $index) {
                [$nom, $marque, $puissance, $capacite, $prix] = EquipementFactory::MODELES[$index];

                // Factory + relations : chaque équipement est rattaché à sa catégorie et à un propriétaire
                Equipement::factory()
                    ->for($categorie)
                    ->for($proprietaires->random(), 'proprietaire')
                    ->count(fake()->numberBetween(1, 2))
                    ->create([
                        'nom' => $nom,
                        'marque' => $marque,
                        'puissance_watts' => $puissance,
                        'capacite_wh' => $capacite,
                        'prix_jour' => $prix,
                        'caution' => $prix * 8,
                    ]);
            }
        }
    }
}
