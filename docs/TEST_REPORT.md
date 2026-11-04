# Rapport de tests NEXORA

## Dernier résultat complet connu

| Vérification | Résultat |
| --- | --- |
| PHPUnit complet | **103 tests, 708 assertions, réussis** |
| Suites ciblées (assistants, Insights, Event Blueprint) | **39 tests, 301 assertions, réussis** |
| Composer | `composer validate --strict` réussi |
| Pint | `vendor/bin/pint --test` réussi après corrections limitées au formatage |
| Vite | `npm run build` réussi |
| Routes et vues | 157 routes ; `php artisan view:cache` réussi |
| OpenRouter réel | Un appel générique a reçu une sortie valide conforme au schéma demandé |
| `git diff --check` | Réussi après les changements applicatifs et documentaires |

## Limites de vérification

- PHPUnit est configuré sur SQLite en mémoire. Les tests ne valident pas l’état de la base MySQL de démonstration.
- `php artisan test` échoue dans le sandbox restreint car PHP renvoie `is_readable=false` sur les fichiers du projet; l’exécution complète a ensuite réussi hors sandbox avec SQLite en mémoire.
- Le `migrate:status` local a été inaccessible sous le sandbox, qui bloque la connexion TCP MySQL. La migration Event Blueprint ciblée a toutefois été appliquée via l’environnement autorisé après l’erreur de table manquante.
- Aucun email n’a été envoyé pour confirmer la réception SMTP.
- Le déploiement CD, Docker Compose et Kubernetes n’ont pas été lancés dans un environnement réel pendant cette validation.
- Un appel OpenRouter générique ne remplace pas une recette live complète de chaque assistant.

## Commandes à rejouer avant remise

```powershell
php artisan test
vendor\bin\pint --test
npm run build
php artisan route:list --except-vendor
```

## Résultats après refonte UX/UI — 4 novembre 2026

| Vérification | Résultat |
| --- | --- |
| PHPUnit direct + SQLite mémoire | **101 tests, 682 assertions, OK** |
| Pint | `vendor\bin\pint --test` réussi |
| Composer | `composer validate --strict` réussi |
| Vite | `npm run build` réussi |
| Blade | `php artisan view:cache` réussi |
| Routes | 157 routes; `GET /assistants` sans middleware auth, les routes membre/BO conservent leurs groupes. |
| HTTP local | `/` 200 avec 5 cartes accueil; `/assistants` 200 avec 5 cartes; SolarMatch/Event Matchmaker 200; les parcours FO protégés donnent 302 sans session; assets CSS/JS 200. |
| Campagne OpenRouter spécialisée | 5/5 réussites HTTP 200 et schémas validés dans `audit-ia-live-20261104-022155.json`; contextes synthétiques, SQLite mémoire, aucune donnée utilisateur. |
| Navigateur visuel | Non certifié : capture et console non disponibles parce que la fenêtre Chrome avait une saisie active. |
| Git diff check | Non exécuté conformément à la consigne de ne faire aucune opération Git. |

L’échec initial du test live Booking venait du runner qui additionnait plusieurs candidats alternatifs comme un panier. Le runner vérifie maintenant chaque option indépendamment; aucune règle métier applicative n’a été modifiée pour obtenir la réussite.

## Revalidation du lot UX/UI — 4 novembre 2026

| Vérification | Résultat |
| --- | --- |
| PHPUnit complet | **103 tests, 708 assertions, OK** |
| Suites ciblées (assistants, Insights, Event Blueprint) | **39 tests, 301 assertions, OK** |
| Pint | `vendor\bin\pint --test` réussi |
| Composer | `composer validate --strict` réussi |
| Vite | `npm run build` réussi |
| Vues Blade | `php artisan view:cache` réussi |
| Routes menu FO | Catalogue, événements, blog, comparateur, recherche et réservation existent |
| PHP / JSON langue | Fichiers de traduction PHP valides; JSON FR/EN/AR valides |
| Navigateur et responsive | Hub affiché dans le navigateur intégré à ~635 px; desktop, mobile étroit, RTL arabe et console JS non vérifiés |
| Git diff check | Non exécuté selon la consigne de ne faire aucune opération Git |
