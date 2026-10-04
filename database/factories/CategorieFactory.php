<?php

namespace Database\Factories;

use App\Models\Categorie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Categorie>
 */
class CategorieFactory extends Factory
{
    protected $model = Categorie::class;

    public function definition(): array
    {
        return [
            'nom' => ucfirst(fake()->unique()->words(2, true)),
            'description' => 'Équipements d\'énergie renouvelable proposés par les membres de la communauté SolarShare.',
            'icone' => fake()->randomElement(array_keys(Categorie::ICONES)),
        ];
    }
}
