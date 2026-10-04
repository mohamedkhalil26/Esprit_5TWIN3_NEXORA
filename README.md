# ☀️ SolarShare

Plateforme de **location et de partage entre particuliers** de petits équipements d'énergie renouvelable
(panneaux solaires portables, batteries, petites éoliennes…).

Projet Applications Web Avancées 2025-2026, réalisé en Laravel 12 par une équipe de 5 membres.

---

## Fonctionnalités

| Module | Membre | Entités (relation) | Back office | Front office |
|---|---|---|---|---|
| Équipements | Membre 1 | `Categorie` 1─N `Equipement` (+ propriétaire `User`) | CRUD catégories, CRUD équipements avec photo | Catalogue avec filtres et tri, fiche détaillée |
| Locations | Membre 2 | `Reservation` 1─N `Paiement` (+ `Equipement`, `User`) | CRUD réservations, CRUD paiements | Réserver un équipement, « Mes réservations » |
| Réclamations | Membre 3 | `Reclamation` 1─N `ReponseReclamation` (+ `User`) | CRUD réclamations, CRUD réponses, fil de discussion | Déposer et suivre ses réclamations |
| Événements | Membre 4 | `Evenement` N─N `User` via `Participation` | CRUD événements, CRUD inscriptions | Agenda, inscription et désinscription, « Mes événements » |
| Blog | Membre 5 | `Article` 1─N `Commentaire` (+ `User`) | CRUD articles, CRUD et modération des commentaires | Blog avec recherche, lecture et commentaires |

**Tâches communes :** authentification (inscription, connexion, rôles `admin` / `user`) et intégration des deux templates.

## Templates utilisés

- **Front office :** [Creative — Start Bootstrap](https://startbootstrap.com/theme/creative)
- **Back office :** [AdminLTE 4](https://adminlte.io)

Personnalisation SolarShare : palette vert et jaune solaire, logo SVG, menus, contenus et pages adaptés.
Les fichiers concernés sont `public/css/solarshare-front.css`, `public/css/solarshare-admin.css` et `public/images/logo.svg`.

## Installation (démo depuis le dépôt Git)

Prérequis : PHP 8.2 ou plus (XAMPP), Composer, MySQL.

```bash
git clone <url-du-depot> solarshare
cd solarshare
composer install
copy .env.example .env
php artisan key:generate
```

1. Créer la base **`solarshare`** dans phpMyAdmin (interclassement `utf8mb4_unicode_ci`).
2. Vérifier `DB_USERNAME` et `DB_PASSWORD` dans `.env`. Avec XAMPP, c'est `root` sans mot de passe.
3. Créer les tables avec les données de démonstration, puis lancer le serveur :

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Ouvrir http://127.0.0.1:8000

### Comptes de démonstration (mot de passe : `password`)

| Rôle | E-mail |
|---|---|
| Administrateur (back office) | `admin@solarshare.tn` |
| Membre (front office) | `client@solarshare.tn` |

## Architecture : comment les modules s'intègrent sans conflit

Chaque membre travaille **uniquement dans ses propres fichiers**. La base les découvre automatiquement :

| Le module fournit… | …et la base l'intègre automatiquement |
|---|---|
| `routes/modules/<module>.php` | chargé par la boucle `glob` de `routes/web.php` |
| `resources/views/modules/<module>/hooks/admin-menu.blade.php` | menu latéral du back office |
| `resources/views/modules/<module>/hooks/front-menu.blade.php` | barre de navigation du front office |
| `resources/views/modules/<module>/hooks/user-menu.blade.php` | menu déroulant du membre connecté |
| `resources/views/modules/<module>/hooks/dashboard.blade.php` | indicateurs du tableau de bord admin |
| `resources/views/modules/<module>/hooks/home.blade.php` | section sur la page d'accueil |
| `database/seeders/<Module>ModuleSeeder.php` | appelé par `DatabaseSeeder` s'il existe |

La découverte des hooks est faite par `app/Support/Modules.php`.

### Héritage Blade

- Layouts maîtres : `resources/views/layouts/front.blade.php` et `resources/views/layouts/admin.blade.php` (`@yield`, `@stack`).
- Partials : `front/partials/*` et `admin/partials/*` (`@include`).
- Composants Blade : `<x-admin.card>`, `<x-delete-button>`, `<x-empty-state>` (`resources/views/components`).
- Chaque page : `@extends('layouts.admin')` + `@section('content')`.
