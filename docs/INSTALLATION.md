# Installation locale NEXORA

## Prérequis

- PHP 8.2 ou 8.3, extensions Laravel usuelles et PDO MySQL.
- Composer 2, Node.js 22/npm, MySQL 8.
- Une base locale dédiée (`solarshare` par défaut dans `.env.example`).

## Mise en route

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Configurer `.env` : `APP_URL`, `DB_*`, `MAIL_*`, `OPENROUTER_API_KEY` et `OPENROUTER_MODEL`. Ne pas afficher ni commiter les secrets. Pour une base existante, contrôler d’abord l’environnement et l’état des migrations ; appliquer uniquement les migrations additives attendues avec `php artisan migrate`. Ne jamais lancer `migrate:fresh` ou `db:wipe`.

```powershell
npm ci
npm run build
php artisan serve --port=8010
```

Les migrations créent les structures de sécurité, conversations IA, quiz et brouillons Event Blueprint. Pour créer des données d’exemple, n’utiliser `php artisan db:seed` que sur une base de démonstration isolée et vide : le seeder n’est pas idempotent pour les comptes de démonstration.

## Email et OpenRouter

Le projet envoie les liens de vérification et de réinitialisation par mail. Configurer un SMTP testable (Mailpit local recommandé pour une soutenance) et confirmer la réception réelle. Détails : [CONFIGURATION_EMAIL_GMAIL.md](CONFIGURATION_EMAIL_GMAIL.md).

OpenRouter est facultatif pour démarrer les pages non génératives. Sans `OPENROUTER_API_KEY`, l’application doit afficher une erreur claire et ne pas fabriquer de réponse. Les tests mockent le fournisseur et ne nécessitent aucune clé.

## Comptes de démonstration

Le seeder prévoit `admin@solarshare.tn` et `client@solarshare.tn` avec le mot de passe de démonstration `password`. Ne les utilisez que sur la base dédiée qui les contient. Changez ou supprimez ces identifiants avant tout déploiement public.

## Contrôles locaux

```powershell
php artisan test
vendor\bin\pint --test
npm run build
php artisan route:list --except-vendor
git diff --check
```

En local, si PHPUnit ne résout pas le bootstrap Windows via `artisan test`, le lancer directement depuis la racine avec la configuration SQLite de `phpunit.xml` ou le binaire `vendor/bin/phpunit`.
