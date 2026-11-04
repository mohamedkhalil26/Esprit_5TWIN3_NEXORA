# Installation des fonctionnalités IA NEXORA

> Pour la procédure courante après les lots IA et la migration Event Blueprint, utiliser [INSTALLATION.md](INSTALLATION.md). Certaines notes de migration ci-dessous sont conservées comme historique et ne décrivent pas nécessairement l’état courant de la base.

## Prérequis

- PHP 8.3 ou compatible avec Laravel 12 ;
- Composer et les dépendances présentes dans `composer.lock` ;
- Node.js/npm pour le build Vite ;
- MySQL configuré par `.env` pour l'instance applicative ;
- une clé `OPENROUTER_API_KEY` uniquement côté serveur si les fonctionnalités de génération doivent être utilisées.

La clé OpenRouter ne doit jamais être ajoutée au dépôt, aux vues, au navigateur ou aux journaux. Les tests automatisés utilisent des réponses HTTP simulées et ne consomment pas de quota.

## Installation locale

Depuis la racine du projet :

```powershell
composer install
npm ci
php artisan config:clear
php artisan migrate:status
php artisan migrate --force
npm run build
```

Les migrations IA sont additives :

- `2026_11_04_000001_create_ai_intelligence_tables` crée les exécutions IA, feedbacks, préférences et notifications ;
- `2026_11_04_000002_create_ai_quiz_tables` crée les définitions de quiz et tentatives versionnées.

Elles ne modifient pas les tables métier existantes et ne suppriment aucune donnée. Vérifier la cible de la connexion avant `migrate --force`, surtout en production. Ne jamais utiliser `migrate:fresh` ou `db:wipe` sur la base historique.

## Fonctionnalités accessibles

| Fonction | Accès |
| --- | --- |
| Centre IA FO/BO | `/assistants` |
| Conversations FO | `/assistants/front/{module}` |
| Conversations BO | `/admin/assistants/{module}` |
| Recherche locale autorisée | `/recherche-intelligente` |
| Notifications personnelles | `/notifications` |
| Feedback BO | `/admin/assistants/feedback` |
| Quiz d'un article publié | `/blog/{article}/quiz` |
| Choix FR/EN/AR | sélecteur de langue dans la navigation |

Les routes BO exigent le rôle `admin`. Les données privées de réservations et réclamations sont limitées à l'utilisateur courant. Les quiz affichent les choix mais jamais le `answer_key`.

## Vérifications

```powershell
php artisan test
vendor\bin\pint --test
php artisan route:list --except-vendor
php artisan view:cache
php artisan migrate:status
npm run build
git diff --check
```

Les tests OpenRouter sont simulés. Pour un appel réel, vérifier d'abord la clé, le modèle, le quota et l'autorisation de l'opérateur. Un timeout, une erreur HTTP ou un 429 doit rester visible comme erreur ; aucune réponse de remplacement n'est générée.

## Données et maintenance

- `ai_feature_runs` conserve des métadonnées bornées, un hash d'entrée, un statut et une durée, pas le prompt complet ;
- les messages de conversation restent chiffrés par le cast Laravel existant ;
- `ai_feedback` référence l'utilisateur et, si disponible, l'exécution IA ;
- `smart_notifications` utilise une clé de déduplication et respecte les préférences ;
- les tentatives de quiz sont corrigées côté Laravel avec un corrigé serveur chiffré ;
- le stock multi-exemplaires n'est pas activé : l'application utilise encore la disponibilité booléenne et les réservations actives des équipements uniques.

Avant déploiement, prévoir sauvegarde, exécution des migrations sur une cible confirmée, contrôle des permissions des secrets et vérification manuelle des parcours FO/BO en français, anglais et arabe.
