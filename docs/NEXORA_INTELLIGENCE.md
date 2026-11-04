# NEXORA Intelligence

## Portée

NEXORA Intelligence fournit cinq expériences Front Office et cinq expériences Back Office : équipements, réservations, réclamations, événements et blog. Le Front Office reçoit uniquement le contexte public ou appartenant à l’utilisateur connecté. Le Back Office utilise des agrégats et des éléments de catalogue nécessaires, sans transmettre les identités des utilisateurs.

Les nouvelles routes sont accessibles après authentification et vérification de l’adresse :

| Audience | Route | Rôle |
|---|---|---|
| Hub | `/assistants` | Utilisateur connecté ; les cartes BO sont ajoutées pour un administrateur |
| Front Office | `/assistants/front/{module}` | `equipements`, `locations`, `reclamations`, `evenements`, `blog` |
| Back Office | `/admin/assistants/{module}` | Administrateur uniquement |

Les générations se font par POST et sont limitées par le middleware `throttle:ai`. Les conversations sont isolées par propriétaire, module et audience. Les contenus de messages sont chiffrés via le cast Laravel `encrypted`. Les recommandations de références sont rechargées depuis la base et filtrées à nouveau avant leur affichage. Les brouillons et analyses ne modifient aucune donnée métier automatiquement.

## Architecture

- `OpenRouterService` effectue l’appel côté serveur, applique les délais, distingue les erreurs HTTP et valide les réponses JSON avec les règles Laravel. Une seule reprise est permise pour une sortie tronquée ou invalide ; un HTTP 429 n’est pas relancé.
- `AiContextBuilder` construit des contextes distincts FO/BO à partir des modèles du projet, filtre les dates, budgets et conflits de réservations, et valide les identifiants proposés contre les candidats.
- `AiConversationService` limite l’historique transmis, conserve les messages chiffrés et journalise uniquement les métadonnées techniques.
- `AiConversationController` valide les entrées, l’audience, le module et la propriété des conversations.
- `resources/views/ai/conversation.blade.php` affiche une prévisualisation échappée et des références rechargées depuis les modèles. Aucune sortie HTML du fournisseur n’est exécutée.

Le modèle est sélectionné par `OPENROUTER_MODEL` (valeur de référence `openrouter/free`) et le secret par `OPENROUTER_API_KEY`. Les valeurs doivent rester dans l’environnement local ou dans le gestionnaire de secrets du déploiement ; ne pas les mettre dans Git, une commande journalisée, une capture ou un rapport. La clé n’est jamais envoyée au navigateur.

## Préparation locale

La migration additive `2026_11_03_000001_create_ai_conversations_tables.php` crée `ai_conversations` et `ai_messages`. Elle n’a pas été exécutée dans le cadre de cette intervention. Après vérification de la sauvegarde et de l’environnement cible, appliquer les migrations non encore exécutées avec la procédure habituelle du projet :

```powershell
php artisan migrate:status
php artisan migrate
```

Ne pas utiliser `migrate:fresh`, `db:wipe` ou un reset pour installer l’historique. Vérifier que `APP_KEY` est valide avant la première conversation : les messages sont chiffrés avec la clé Laravel et ne pourront pas être relus après un remplacement de cette clé.

Après la migration et la disponibilité de MySQL, démarrer l’application depuis un PowerShell Windows normal :

```powershell
php artisan config:clear
php artisan serve --port=8010
```

Se connecter avec une adresse vérifiée, ouvrir `/assistants`, puis choisir l’assistant correspondant. Le lien « Tous les assistants » ramène au hub. L’administrateur peut aussi ouvrir les assistants depuis les pages de modules BO. Les pages FO de réclamations, d’événements et de blog restent distinctes des outils de rédaction administratifs.

## Vérifications

Les tests de fonctionnalités utilisent des réponses HTTP simulées pour rester reproductibles ; ils ne prouvent pas la disponibilité d’OpenRouter. La configuration PHPUnit cible SQLite en mémoire (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), tandis que MySQL n’est pas nécessaire aux assertions de ces tests.

Depuis un PowerShell Windows normal, à la racine du dépôt :

```powershell
php artisan test --filter=AiConversationsTest
php artisan test --filter=AiAssistantsTest
php artisan test
vendor\bin\pint --test
php artisan route:list --path=assistants
php artisan view:cache
php artisan view:clear
```

Ne qualifier une réponse de « live » que si le rapport de campagne contient le statut HTTP, le modèle retourné, la validation de schéma et le contrôle de qualité pour cet appel. La preuve OpenRouter antérieure du 3 novembre 2026 a été limitée par des réponses 429 ; elle ne certifie pas le fonctionnement live des cinq assistants de cette nouvelle architecture. Une nouvelle campagne n’a pas été exécutée ici.

## Scénario de démonstration

1. **Équipements FO** : décrire un besoin fictif et un budget ; vérifier que les cartes correspondent aux équipements disponibles et que la fiche réelle s’ouvre.
2. **Réservations FO** : choisir des dates futures et un budget ; demander une recommandation ; comparer la période au calendrier et ouvrir le formulaire. L’assistant ne crée ni ne confirme la réservation.
3. **Réclamations FO** : demander un brouillon sans donnée personnelle ; le copier ou ouvrir le formulaire ; montrer que rien n’est soumis.
4. **Événements et blog FO** : ouvrir un événement ou article public depuis son module ; demander une explication ; les réponses ne doivent citer que les éléments chargés depuis la base.
5. **Back Office** : connecter un administrateur, consulter les indicateurs réels des cinq modules et générer un brouillon événement/blog ou une description d’équipement. Vérifier que les prévisualisations restent éditables et qu’aucune donnée n’est enregistrée automatiquement.
6. **Historique et permissions** : reprendre une conversation de l’utilisateur connecté ; vérifier qu’un autre compte ne peut pas l’ouvrir ; vérifier qu’un compte non administrateur reçoit un refus sur les routes BO.
7. **Limites externes** : si OpenRouter retourne 429, présenter le message de limitation et arrêter les essais ; ne pas remplacer la réponse par un texte fictif.

## État après correction des tests — 4 novembre 2026

La première exécution a identifié deux défauts de code : Eloquent inférait `ai_conversation_id` alors que la migration crée `conversation_id`, ce qui interrompait le service avant l’appel OpenRouter simulé ; puis la vue conversation contenait des directives PHP Blade inline mal compilées. Les deux relations Eloquent déclarent maintenant explicitement `conversation_id`, et les blocs PHP de la vue utilisent la syntaxe bloc `@php … @endphp`. Les tests n’ont pas été modifiés pour masquer les échecs.

Résultats exécutés depuis l’environnement Windows autorisé, avec la configuration PHPUnit SQLite en mémoire :

| Vérification | Résultat |
|---|---|
| `php artisan test --filter=AiConversationsTest` | PASS — 10 tests, 69 assertions |
| `php artisan test --filter=AiAssistantsTest` | PASS — 22 tests, 164 assertions |
| `php artisan test` | PASS — 56 tests, 394 assertions |
| `vendor/bin/pint --test` | PASS |
| `php artisan route:list --path=assistants` | PASS — 10 routes affichées |
| `php artisan view:cache` | PASS |

Ces tests ne se sont pas connectés à MySQL : `phpunit.xml` définit `DB_CONNECTION=sqlite` et `DB_DATABASE=:memory:`. La migration d’historique n’a pas été appliquée à la base MySQL. Les appels live OpenRouter de cette nouvelle architecture et la revue visuelle navigateur restent à effectuer ; les résultats live antérieurs avec réponses 429 ne les remplacent pas.

## Diagnostic OpenRouter — 4 novembre 2026

Le journal local contient un `curl_error` 28 à 00:30:00 (timeout, sans statut HTTP), puis deux réponses HTTP 429 à 00:30:28 et 00:32:02. Pour ces deux réponses, aucune valeur exploitable `Retry-After` n’apparaît dans le diagnostic enregistré ; le journal ne permet pas de savoir si l’en-tête était absent ou invalide, ni de distinguer la catégorie précise de quota fournisseur. Le contenu brut de la réponse fournisseur n’est volontairement pas journalisé.

La configuration effective contrôlée sans afficher le secret indique le modèle `openrouter/free`, un timeout de requête de 60 secondes, un timeout de connexion de 5 secondes et une clé présente. Les échecs réseau n’étaient pas retentés automatiquement ; les retries existants concernent seulement les sorties JSON tronquées ou invalides. Le code affiche maintenant un message distinct pour le timeout cURL 28. Quand OpenRouter fournit un `Retry-After` valide, le délai est présenté à l’utilisateur et une fenêtre de refroidissement empêche un second appel avant son échéance. Aucun texte de secours ne simule une réponse.

Après ces corrections, `AiConversationsTest` passe avec 10 tests / 69 assertions, `AiAssistantsTest` avec 22 / 164, et la suite complète avec 56 / 394. Pint passe. Les appels OpenRouter réels n’ont pas été relancés durant cette vérification.
