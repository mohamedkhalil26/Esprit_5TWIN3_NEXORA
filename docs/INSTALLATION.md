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

### Diagnostiquer la partie IA

```powershell
php artisan ai:doctor
```

La commande vérifie la présence de la clé, l'existence des modèles configurés dans le catalogue OpenRouter, l'état du quota et effectue un appel de génération minimal. Elle n'affiche jamais la clé.

| Variable | Rôle | Défaut |
| --- | --- | --- |
| `OPENROUTER_MODEL` | Modèle principal (`openrouter/free` route vers un modèle gratuit) | modèle gratuit Gemma |
| `OPENROUTER_FALLBACK_MODEL` | Second modèle essayé sur panne temporaire | Gemma 26B gratuit |
| `OPENROUTER_TIMEOUT` | Délai d'une tentative | 60 s |
| `OPENROUTER_TOTAL_BUDGET` | Budget total d'une génération, toutes tentatives | 110 s |
| `OPENROUTER_CONNECT_TIMEOUT` | Délai de connexion | 10 s |
| `OPENROUTER_JSON_MODE` | Envoie `response_format=json_object` quand le modèle le supporte | `true` |
| `OPENROUTER_RATE_LIMIT_COOLDOWN` | Attente après un 429 sans `Retry-After` | 60 s |
| `OPENROUTER_MAX_RATE_LIMIT_COOLDOWN` | Plafond du cooldown, même avec un `Retry-After` long | 21600 s |

### Comprendre un HTTP 429

Un `429` vient d'OpenRouter, pas de Laravel. Les quotas gratuits sont partagés entre tous les modèles `:free` d'un même compte : changer de modèle gratuit ne contourne pas un quota journalier épuisé. Le service :

- lit `Retry-After` et les métadonnées équivalentes, le plafonne et mémorise un cooldown ;
- refuse immédiatement une nouvelle génération pendant ce cooldown au lieu de consommer du quota ;
- n'essaie aucun modèle de secours après un 429, inutile sur un quota partagé ;
- journalise `openrouter.request_failed` avec la portée, le statut, le `finish_reason` et les durées, sans prompt ni secret.

Causes fréquentes : quota journalier gratuit atteint, limite par minute du fournisseur, ou compte sans crédit. Solutions : attendre le renouvellement indiqué, ajouter un crédit OpenRouter, ou configurer un modèle autorisé dans `OPENROUTER_MODEL`.

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

