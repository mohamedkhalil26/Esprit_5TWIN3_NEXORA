<?php

use App\Models\Article;
use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

$app = require __DIR__.'/bootstrap.php';
$db = config('database.connections.sqlite.database');
if (file_exists($db)) {
    throw new RuntimeException('La base de recette existe déjà. Utiliser les données existantes ou archiver ce dossier avant une nouvelle recette.');
}
touch($db);
Artisan::call('migrate', ['--force' => true]);
$admin = User::factory()->admin()->create(['name' => 'Admin Recette', 'email' => 'admin.recette@example.test', 'password' => 'Recette2026!']);
$user = User::factory()->create(['name' => 'Membre Recette', 'email' => 'membre.recette@example.test', 'password' => 'Recette2026!']);
$category = Categorie::factory()->create(['nom' => 'Solaire recette']);
Equipement::factory()->create(['categorie_id' => $category->id, 'user_id' => $admin->id, 'nom' => 'Batterie recette 1000 Wh', 'disponible' => true, 'prix_jour' => 20, 'caution' => 100, 'puissance_watts' => 500, 'capacite_wh' => 1000]);
Equipement::factory()->create(['categorie_id' => $category->id, 'user_id' => $admin->id, 'nom' => 'Panneau recette 200 W', 'disponible' => true, 'prix_jour' => 10, 'caution' => 50, 'puissance_watts' => 200]);
Evenement::factory()->create(['titre' => 'Atelier recette solaire', 'statut' => 'planifie', 'date_debut' => now()->addDays(7), 'date_fin' => now()->addDays(7)->addHours(2), 'capacite' => 1]);
Article::factory()->create(['user_id' => $admin->id, 'titre' => 'Conseils solaires pour la recette finale', 'statut' => 'publie', 'publie_le' => now()->subDay()]);
Article::factory()->create(['user_id' => $admin->id, 'titre' => 'Article confidentiel avant publication', 'statut' => 'publie', 'publie_le' => now()->addDays(3)]);
echo "Instance de recette initialisée : SQLite séparé, comptes et contenus jetables.\n";
