# Finalisation des assistants IA NEXORA

Date de campagne : 3 novembre 2026. Les appels live ci-dessous ont utilisé des demandes et des données synthétiques. Le runner était configuré sur SQLite `:memory:` ; aucune connexion à MySQL ni écriture de contenu dans l’application n’a été effectuée.

## Résultat de la campagne OpenRouter

La preuve détaillée, sans secrets, est dans [audit-ia-live-20261103-214028.json](audit-ia-live-20261103-214028.json). Le modèle demandé était `openrouter/free`. Quand OpenRouter a répondu avec succès, son champ `model` indiquait `xingchen-agi/xing4.0-29b-a4b:free`.

| Assistant | Résultat live | Détail |
|---|---|---|
| Équipements | **PASS** | HTTP 200, 26,498 ms, une requête, JSON et schéma Laravel valides. Les trois identifiants sont synthétiques (91001–91003), pas une vérification du catalogue réel. Total évalué : 170 DT pour un jour, sous le budget synthétique de 500 DT. |
| Réservations | **FAIL** | HTTP 429 final, 43,799 ms, deux tentatives au total : une nouvelle génération après une sortie incomplète, puis limitation de débit. Le service n’a pas relancé la requête après le 429. Aucun résultat final à valider. |
| Réclamations | **FAIL** | HTTP 429, 671 ms, une requête. Pas de réponse ni de validation de schéma. |
| Événements | **FAIL qualité** | HTTP 200, 30,912 ms, JSON et schéma valides en une requête. Revue humaine : texte méta (« fictif », « plateforme ») impropre à une annonce aux participants. Le prompt a depuis été renforcé, mais cette modification n’a pas été retestée en live. |
| Blog | **FAIL** | HTTP 429, 2,334 ms, une requête. Pas de réponse ni de validation de schéma. |

`finish_reason`, les statuts, durées, modèles retournés, tentatives et résultats sont conservés dans le JSON. Les réponses d’erreur n’incluent aucun corps fournisseur ni secret. Un assistant n’est pas considéré prêt sur la seule base d’un appel réussi si la qualité éditoriale est insuffisante.

## Corrections apportées

- Ajout du centre `/assistants` (`ai.index`) : les membres voient les assistants Équipements et Réservations ; les administrateurs voient les cinq. Les permissions restent contrôlées côté serveur.
- Ajout d’une page responsive en cartes et de liens depuis les navigations front et back office. Les propositions administratives restent une prévisualisation ; l’application exige une validation explicite.
- Gestion d’erreurs OpenRouter explicite et sûre pour HTTP 400, 401, 402, 403, 429 et erreurs serveur. `Retry-After` est interprété et plafonné ; les erreurs HTTP, notamment 429, ne déclenchent pas de retry.
- Une seule nouvelle génération est permise pour une réponse tronquée/invalide ou un schéma incorrect. Une sortie incomplète n’est jamais présentée comme une proposition valide.
- Renforcement du prompt Événements : contenu destiné aux participants, sans méta-mentions de test ou de données fictives et avec types stricts. L’efficacité de cette amélioration reste à confirmer par une nouvelle campagne live quand le quota le permettra.
- Correction de l’instrumentation du runner pour utiliser les interfaces PSR-7 réellement fournies par le middleware HTTP Laravel/Guzzle.

## Accès et utilisation

- Centre des assistants : `/assistants` (route nommée `ai.index`), authentification et email vérifié requis.
- Formulaire de chaque module : `/assistants/{module}` ; génération POST `/assistants/{module}`. Les noms de modules sont `equipements`, `locations`, `reclamations`, `evenements`, `blog`.
- Les assistants administratifs (Réclamations, Événements, Blog) sont réservés aux administrateurs. Une recommandation de réservation ou d’équipement ne confirme ni ne crée de réservation.
- La génération d’Équipements et Réservations dépend du catalogue réellement disponible et filtré par budget, dates et conflits. La preuve live de cette campagne utilisait des identifiants synthétiques et ne démontre pas la correspondance avec le catalogue MySQL.
- Le modèle est piloté par `OPENROUTER_MODEL` (valeur attendue : `openrouter/free`) et la clé reste dans l’environnement local. Après une modification locale de configuration, effacer le cache avec `php artisan config:clear`. Ne pas copier la clé dans un rapport ou une commande journalisée.
- Lancer une campagne autorisée depuis PowerShell Windows normal : `.\scripts\run-live-ai-campaign.ps1 -Php C:\8.3.31\php.exe`. Le runner produit un JSON horodaté dans `docs/`. La campagne réelle rencontrant actuellement des 429, attendre que la limite OpenRouter soit levée avant de relancer. Chaque résultat doit être vérifié dans le rapport ; un code de sortie non nul signifie que la campagne n’a pas entièrement réussi.

## Vérifications automatisées et limites de l’environnement

- Le test ciblé `AiAssistantsTest` a précédemment réussi : 18 tests, 137 assertions, après les corrections principales et avant les toutes dernières assertions de navigation/interception middleware.
- Une suite complète antérieure a réussi : 42 tests, 296 assertions, également avant les derniers ajouts de tests. Ces chiffres sont rapportés comme résultats antérieurs, pas comme résultat d’une exécution finale.
- La tentative de relance complète dans l’environnement Codex échoue avant les tests : PHPUnit signale qu’il ne peut pas ouvrir `vendor/autoload.php`. Le même projet a déjà été confirmé fonctionnel depuis un PowerShell Windows hors sandbox (`OK (33 tests, 216 assertions)` à un stade antérieur). Il faut donc relancer la suite depuis PowerShell normal et consigner son résultat actualisé.
- Build Vite, cache/compilation des routes et vues, ainsi que Pint avaient été vérifiés avant les derniers changements UI/tests. La vérification visuelle réelle des tailles desktop/tablette/mobile n’a pas pu être certifiée dans ce passage ; les cartes utilisent les classes responsive Bootstrap et des tests vérifient les liens/rendus, mais cela ne remplace pas une inspection navigateur.
- Aucun changement de `.env`, aucun accès/écriture MySQL, aucune migration, publication, commit, push ou déploiement n’a été effectué durant cette finalisation.

## État pour la démonstration

Le centre et les contrôles d’accès sont présentables après exécution de la suite complète depuis PowerShell normal. L’intégration technique OpenRouter est prouvée pour Équipements et la structure Événements, mais seuls Équipements est **PASS** global. Réservations, Réclamations et Blog restent bloqués par le 429 live ; Événements nécessite la confirmation live du prompt amélioré et une revue humaine. La démonstration doit présenter ces limites honnêtement et ne pas simuler une réponse live.
